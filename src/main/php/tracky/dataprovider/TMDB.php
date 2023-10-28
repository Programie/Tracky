<?php
namespace tracky\dataprovider;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class TMDB
{
    private Client $client;

    public function __construct(string $apiToken)
    {
        $this->client = new Client([
            "base_uri" => "https://api.themoviedb.org/3/",
            RequestOptions::HEADERS => [
                "Authorization" => sprintf("Bearer %s", $apiToken)
            ]
        ]);
    }

    public function getShowData(int $id): array
    {
        $data = json_decode($this->client->get(sprintf("tv/%d", $id))->getBody()->getContents(), true);

        return [
            "posterImageUrl" => sprintf("https://image.tmdb.org/t/p/w500/%s", ltrim($data["poster_path"], "/"))
        ];
    }

    public function getMovieData(int $id): array
    {
        $data = json_decode($this->client->get(sprintf("movie/%d", $id))->getBody()->getContents(), true);

        return [
            "posterImageUrl" => sprintf("https://image.tmdb.org/t/p/w500/%s", ltrim($data["poster_path"], "/"))
        ];
    }
}