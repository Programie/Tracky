<?php
namespace tracky\dataprovider;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class TMDB
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

    public function getShowData(int $id, ?string $language): array
    {
        $data = $this->getJson(sprintf("tv/%d", $id), ["language" => $language ?? $this->defaultShowLanguage]);

        $seasons = [];

        foreach ($data["seasons"] ?? [] as $season) {
            $posterImageUrl = $season["poster_path"] ?? null;
            if ($posterImageUrl !== null) {
                $posterImageUrl = sprintf("https://image.tmdb.org/t/p/w500/%s", ltrim($posterImageUrl, "/"));
            }

            $season["posterImageUrl"] = $posterImageUrl;

            $seasons[] = $season;
        }

        return [
            "title" => $data["name"],
            "posterImageUrl" => sprintf("https://image.tmdb.org/t/p/w500/%s", ltrim($data["poster_path"], "/")),
            "seasons" => $seasons
        ];
    }

    public function getShowEpisodes(int $showId, int $season, ?string $language): array
    {
        $data = $this->getJson(sprintf("tv/%d/season/%d", $showId, $season), ["language" => $language ?? $this->defaultShowLanguage]);

        $episodes = [];

        foreach ($data["episodes"] ?? [] as $episode) {
            $posterImageUrl = $episode["still_path"] ?? null;
            if ($posterImageUrl !== null) {
                $posterImageUrl = sprintf("https://image.tmdb.org/t/p/w500/%s", ltrim($posterImageUrl, "/"));
            }

            $episode["posterImageUrl"] = $posterImageUrl;

            $episodes[] = $episode;
        }

        return $episodes;
    }

    public function getMovieData(int $id, ?string $language): array
    {
        $data = $this->getJson(sprintf("movie/%d", $id), ["language" => $language ?? $this->defaultMovieLanguage]);

        return [
            "title" => $data["title"],
            "posterImageUrl" => sprintf("https://image.tmdb.org/t/p/w500/%s", ltrim($data["poster_path"], "/"))
        ];
    }

    public function getTmdbIdFromExternalId(string $externalSource, string $externalId, string $expectedMediaType): ?int
    {
        $data = $this->getJson(sprintf("find/%s", $externalId), ["external_source" => $externalSource]);

        foreach ($data as $results) {
            foreach ($results as $result) {
                if ($result["media_type"] === $expectedMediaType) {
                    return $result["id"];
                }
            }
        }

        return null;
    }
}