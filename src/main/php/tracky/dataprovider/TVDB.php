<?php
namespace tracky\dataprovider;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\UriResolver;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\RequestOptions;
use tracky\datetime\Date;
use tracky\datetime\DateTime;
use tracky\model\Episode;
use tracky\model\Movie;
use tracky\model\Season;
use tracky\model\Show;

class TVDB implements Provider
{
    private Client $client;

    public function __construct(string $authToken)
    {
        $this->client = new Client([
            "base_uri" => "https://api4.thetvdb.com/v4/",
            RequestOptions::HEADERS => [
                "Authorization" => sprintf("Bearer %s", $authToken)
            ]
        ]);
    }

    private function getJson(string $path, array $query = []): ?array
    {
        $request = $this->client->get($path, [
            RequestOptions::QUERY => $query
        ]);

        return json_decode($request->getBody()->getContents(), true)["data"] ?? null;
    }

    private function getTvdbIdFromRemoteId(string $remoteId): ?int
    {
        $data = $this->getJson(sprintf("search/remoteid/%s", $remoteId));
        if ($data === null) {
            return null;
        }

        foreach ($data as $results) {
            foreach ($results as $result) {
                return $result["id"];
            }
        }

        return null;
    }

    private function getImageUrl(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        return UriResolver::resolve(Utils::uriFor("https://artworks.thetvdb.com"), Utils::uriFor($url))->__toString();
    }

    private function setEpisodeData(Episode $episode, array $data): void
    {
        $episode->setTitle($data["name"]);
        $episode->setPlot($data["overview"] ?? null);
        $episode->setPosterImageUrl($this->getImageUrl($data["image"] ?? null));

        $aired = $data["aired"] ?? null;
        $episode->setFirstAired($aired === null ? null : new Date($aired));
    }

    public function getIdFieldName(): string
    {
        return "tvdbId";
    }

    public function setIdForShow(Show $show, mixed $id): void
    {
        $show->setTvdbId($id);
    }

    public function setIdForMovie(Movie $movie, mixed $id): void
    {
        $movie->setTvdbId($id);
    }

    public function getIdFromUniqueIds(array $uniqueIds): ?int
    {
        $tvdbId = $uniqueIds["tvdb"] ?? null;
        if ($tvdbId !== null) {
            $tvdbId = (int)$tvdbId;
            if ($tvdbId === 0) {
                return null;
            }

            return $tvdbId;
        }

        $externalSources = ["imdb", "tmdb"];

        foreach ($externalSources as $provider) {
            $uniqueId = $uniqueIds[$provider] ?? null;
            if ($uniqueId === null) {
                continue;
            }

            $tvdbId = $this->getTvdbIdFromRemoteId($uniqueId);
            if ($tvdbId !== null) {
                return $tvdbId;
            }
        }

        return null;
    }

    public function fetchShow(Show $show, bool $createSeasonsAndEpisodes): bool
    {
        $tvdbId = $show->getTvdbId();
        if ($tvdbId === null) {
            return false;
        }

        $data = $this->getJson(sprintf("series/%d/extended", $tvdbId), ["meta" => "episodes", "short" => "true"]);
        if ($data === null) {
            return false;
        }

        $show->setTitle($data["name"]);
        $show->setPosterImageUrl($this->getImageUrl($data["image"]));
        $show->setStatus($this->mapShowStatus($data["status"]["name"] ?? ""));

        if ($createSeasonsAndEpisodes) {
            foreach ($data["seasons"] ?? [] as $seasonData) {
                $seasonType = $seasonData["type"]["type"] ?? null;
                if ($seasonType !== "official") {
                    continue;
                }

                $seasonNumber = $seasonData["number"];

                $season = $show->getOrCreateSeason($seasonNumber);

                $season->setPosterImageUrl($this->getImageUrl($seasonData["image"] ?? null));
            }

            foreach ($data["episodes"] ?? [] as $episodeData) {
                $seasonNumber = $episodeData["seasonNumber"];
                $episodeNumber = $episodeData["number"];

                $season = $show->getOrCreateSeason($seasonNumber);
                $episode = $season->getOrCreateEpisode($episodeNumber);

                $this->setEpisodeData($episode, $episodeData);
            }
        }

        $show->setLastUpdate(new DateTime);

        return true;
    }

    public function fetchSeason(Season $season, bool $createEpisodes): bool
    {
        $show = $season->getShow();
        $tvdbId = $show->getTvdbId();
        if ($tvdbId === null) {
            return false;
        }

        $data = $this->getJson(sprintf("series/%d/episodes/default", $tvdbId), [
            "page" => 0,
            "season" => $season->getNumber()
        ]);

        if ($data === null) {
            return false;
        }

        foreach ($data["episodes"] ?? [] as $episodeData) {
            $episode = $season->getOrCreateEpisode($episodeData["number"]);

            $this->setEpisodeData($episode, $episodeData);
        }

        return true;
    }

    public function fetchEpisode(Episode $episode): bool
    {
        $season = $episode->getSeason();
        $show = $season->getShow();
        $tvdbId = $show->getTvdbId();
        if ($tvdbId === null) {
            return false;
        }

        $data = $this->getJson(sprintf("series/%d/episodes/default", $tvdbId), [
            "page" => 0,
            "season" => $season->getNumber(),
            "episodeNumber" => $episode->getNumber()
        ]);

        $data = $data["episodes"][0] ?? null;
        if ($data === null) {
            return false;
        }

        $this->setEpisodeData($episode, $data);

        return true;
    }

    public function fetchMovie(Movie $movie): bool
    {
        $tvdbId = $movie->getTvdbId();
        if ($tvdbId === null) {
            return false;
        }

        $data = $this->getJson(sprintf("movies/%d", $tvdbId));
        if ($data === null) {
            return false;
        }

        $year = $data["year"] ?? null;
        if ($year !== null) {
            $year = (int)$year;
        }

        $movie->setTitle($data["name"]);
        $movie->setPlot(null);// TODO
        $movie->setYear($year);
        $movie->setPosterImageUrl($this->getImageUrl($data["image"]));

        return true;
    }

    public function searchShow(string $query, ?int $year): array
    {
        return $this->search("series", $query, $year);
    }

    public function searchMovie(string $query, ?int $year): array
    {
        return $this->search("movie", $query, $year);
    }

    private function search(string $type, string $query, ?int $year): array
    {
        $response = $this->getJson("search", [
            "query" => $query,
            "type" => $type,
            "year" => $year
        ]);

        $items = [];

        foreach ($response ?? [] as $result) {
            $items[] = [
                "id" => $result["tvdb_id"],
                "title" => $result["name"],
                "year" => $result["year"] ?? null,
                "image" => $result["image_url"]
            ];
        }

        return $items;
    }

    private function mapShowStatus(string $status): ?string
    {
        return match ($status) {
            "Upcoming" => Show::STATUS_UPCOMING,
            "Continuing" => Show::STATUS_CONTINUING,
            "Ended" => Show::STATUS_ENDED,
            default => null,
        };
    }
}