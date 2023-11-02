<?php
namespace tracky\dataprovider;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use tracky\datetime\Date;
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

    private function setEpisodeData(Episode $episode, array $data): void
    {
        $episode->setTitle($data["name"]);
        $episode->setPlot($data["overview"] ?? null);
        $episode->setPosterImageUrl($data["image"] ?? null);
        $episode->setFirstAired(new Date($data["aired"]));
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

    public function getIdFromUniqueIds(array $uniqueIds): mixed
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
        $show->setPosterImageUrl($data["image"]);

        if ($createSeasonsAndEpisodes) {
            foreach ($data["episodes"] ?? [] as $episodeData) {
                $seasonNumber = $episodeData["seasonNumber"];
                $episodeNumber = $episodeData["number"];

                $season = $show->getOrCreateSeason($seasonNumber);
                $episode = $season->getOrCreateEpisode($episodeNumber);

                $this->setEpisodeData($episode, $episodeData);
            }
        }

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

        $movie->setTitle($data["name"]);
        $movie->setPlot(null);// TODO
        $movie->setYear($data["year"]);
        $movie->setPosterImageUrl($data["image"]);

        return true;
    }
}