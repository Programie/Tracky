<?php
namespace tracky\dataprovider;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use tracky\datetime\Date;
use tracky\datetime\DateTime;
use tracky\model\Episode;
use tracky\model\Movie;
use tracky\model\Season;
use tracky\model\Show;

class TMDB implements Provider
{
    private Client $client;

    public function __construct(
        string                  $apiToken,
        private readonly string $defaultMovieLanguage,
        private readonly string $defaultShowLanguage
    )
    {
        $this->client = new Client([
            "base_uri" => "https://api.themoviedb.org/3/",
            RequestOptions::HEADERS => [
                "Authorization" => sprintf("Bearer %s", $apiToken)
            ]
        ]);
    }

    private function getJson(string $path, array $query = []): array
    {
        $request = $this->client->get($path, [
            RequestOptions::QUERY => $query
        ]);

        return json_decode($request->getBody()->getContents(), true);
    }

    private function getLocalizedJson(string $path, ?string $language, string $defaultLanguage): array
    {
        return $this->getJson($path, ["language" => $language ?? $defaultLanguage]);
    }

    private function getShowJson(Show $show, ?string $path = null): array
    {
        $pathArray = [
            "tv",
            $show->getTmdbId()
        ];

        if ($path !== null) {
            $pathArray[] = $path;
        }

        return $this->getLocalizedJson(implode("/", $pathArray), $show->getLanguage(), $this->defaultShowLanguage);
    }

    private function getTmdbIdFromExternalId(string $externalSource, string $externalId): ?int
    {
        $data = $this->getJson(sprintf("find/%s", $externalId), ["external_source" => $externalSource]);

        foreach ($data as $results) {
            foreach ($results as $result) {
                return $result["id"];
            }
        }

        return null;
    }

    private function getImageUrl(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        return sprintf("https://image.tmdb.org/t/p/w500/%s", ltrim($path, "/"));
    }

    private function setEpisodeData(Episode $episode, array $data): void
    {
        $episode->setTitle($data["name"]);
        $episode->setPlot($data["overview"] ?? null);
        $episode->setPosterImageUrl($this->getImageUrl($data["still_path"] ?? null));
        $episode->setRuntime($data["runtime"] ?? null);

        $airDate = $data["air_date"] ?? null;
        $episode->setFirstAired($airDate === null ? null : new Date($airDate));
    }

    public function getIdFieldName(): string
    {
        return "tmdbId";
    }

    public function setIdForShow(Show $show, mixed $id): void
    {
        $show->setTmdbId($id);
    }

    public function setIdForMovie(Movie $movie, mixed $id): void
    {
        $movie->setTmdbId($id);
    }

    public function getIdFromUniqueIds(array $uniqueIds): ?int
    {
        $tmdbId = $uniqueIds["tmdb"] ?? null;
        if ($tmdbId !== null) {
            $tmdbId = (int)$tmdbId;
            if ($tmdbId === 0) {
                return null;
            }

            return $tmdbId;
        }

        $externalSources = [
            "imdb" => "imdb_id",
            "tvdb" => "tvdb_id"
        ];

        foreach ($externalSources as $provider => $externalSource) {
            $uniqueId = $uniqueIds[$provider] ?? null;
            if ($uniqueId === null) {
                continue;
            }

            $tmdbId = $this->getTmdbIdFromExternalId($externalSource, $uniqueId);
            if ($tmdbId !== null) {
                return $tmdbId;
            }
        }

        return null;
    }

    public function fetchShow(Show $show, bool $createSeasonsAndEpisodes): bool
    {
        $tmdbId = $show->getTmdbId();
        if ($tmdbId === null) {
            return false;
        }

        $showData = $this->getShowJson($show);

        $show->setTitle($showData["name"] ?? "");
        $show->setPosterImageUrl($this->getImageUrl($showData["poster_path"] ?? null));
        $show->setStatus($this->mapShowStatus($showData["status"] ?? ""));

        $externalIds = $this->getJson(sprintf("tv/%d/external_ids", $tmdbId));

        $tvdb_id = $externalIds["tvdb_id"] ?? null;
        if ($tvdb_id) {
            $show->setTvdbId($tvdb_id);
        }

        if ($createSeasonsAndEpisodes) {
            foreach ($showData["seasons"] ?? [] as $seasonData) {
                $season = $show->getOrCreateSeason($seasonData["season_number"]);

                $this->fetchSeason($season, true);
            }

            $show->setLastUpdate(new DateTime);// Only set last update if seasons/episodes were also updated
        }

        return true;
    }

    public function fetchSeason(Season $season, bool $createEpisodes): bool
    {
        $show = $season->getShow();

        if ($show->getTmdbId() === null) {
            return false;
        }

        $seasonData = $this->getShowJson($show, sprintf("season/%d", $season->getNumber()));

        $season->setPosterImageUrl($this->getImageUrl($seasonData["poster_path"] ?? null));

        if ($createEpisodes) {
            foreach ($seasonData["episodes"] ?? [] as $episodeData) {
                $episode = $season->getOrCreateEpisode($episodeData["episode_number"]);

                $this->setEpisodeData($episode, $episodeData);
            }
        }

        return true;
    }

    public function fetchEpisode(Episode $episode): bool
    {
        $season = $episode->getSeason();
        $show = $season->getShow();

        $tmdbId = $show->getTmdbId();
        if ($tmdbId === null) {
            return false;
        }

        $episodeData = $this->getShowJson($show, sprintf("season/%d/episode/%d", $season->getNumber(), $episode->getNumber()));

        $this->setEpisodeData($episode, $episodeData);

        return true;
    }

    public function fetchMovie(Movie $movie): bool
    {
        $tmdbId = $movie->getTmdbId();
        if ($tmdbId === null) {
            return false;
        }

        $movieData = $this->getLocalizedJson(sprintf("movie/%d", $tmdbId), $movie->getLanguage(), $this->defaultMovieLanguage);

        $releaseDate = $movieData["release_date"] ?? null;
        if ($releaseDate !== null) {
            try {
                $releaseDate = new Date($releaseDate);
            } catch (Exception) {
                $releaseDate = null;
            }
        }

        $movie->setTitle($movieData["title"]);
        $movie->setTagline($movieData["tagline"] ?? null);
        $movie->setPlot($movieData["overview"] ?? null);
        $movie->setYear($releaseDate === null ? null : (int)$releaseDate->format("Y"));
        $movie->setPosterImageUrl($this->getImageUrl($movieData["poster_path"] ?? null));
        $movie->setRuntime($movieData["runtime"] ?? null);

        $externalIds = $this->getJson(sprintf("movie/%d/external_ids", $tmdbId));

        $tvdb_id = $externalIds["tvdb_id"] ?? null;
        if ($tvdb_id) {
            $movie->setTvdbId($tvdb_id);
        }

        return true;
    }

    public function searchShow(string $query, ?int $year): array
    {
        return $this->search("tv", $query, $year);
    }

    public function searchMovie(string $query, ?int $year): array
    {
        return $this->search("movie", $query, $year);
    }

    private function search(string $type, string $query, ?int $year): array
    {
        $response = $this->getJson(sprintf("search/%s", $type), [
            "query" => $query,
            "year" => $year ?: ""
        ]);

        $items = [];

        foreach ($response["results"] ?? [] as $result) {
            $date = $result["release_date"] ?? $result["first_air_date"] ?? null;

            $items[] = [
                "id" => $result["id"],
                "title" => $result["title"],
                "year" => $date === null ? null : (new Date($date))->format("Y"),
                "image" => $this->getImageUrl($result["poster_path"] ?? null)
            ];
        }

        return $items;
    }

    private function mapShowStatus(string $status): ?string
    {
        return match ($status) {
            "In Production", "Planned" => Show::STATUS_UPCOMING,
            "Pilot", "Returning Series" => Show::STATUS_CONTINUING,
            "Canceled", "Ended" => Show::STATUS_ENDED,
            default => null,
        };
    }
}