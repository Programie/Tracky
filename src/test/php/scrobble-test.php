#!/usr/bin/env php
<?php
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

require_once __DIR__ . "/../../../vendor/autoload.php";

$scrobbles = [
    [
        "event" => "start",
        "mediaType" => "episode",
        "uniqueIds" => [
            "tvdb" => "75897"
        ],
        "season" => 1,
        "episode" => 1
    ],
    [
        "event" => "end",
        "mediaType" => "episode",
        "timestamp" => (new DateTime)->sub(new DateInterval("P7D"))->format("c"),
        "uniqueIds" => [
            "tvdb" => "75897"
        ],
        "season" => 20,
        "episode" => 1
    ],
    [
        "event" => "end",
        "mediaType" => "episode",
        "timestamp" => (new DateTime)->sub(new DateInterval("P2D"))->format("c"),
        "uniqueIds" => [
            "tvdb" => "71663"
        ],
        "season" => 32,
        "episode" => 15
    ],
    [
        "event" => "end",
        "mediaType" => "movie",
        "timestamp" => (new DateTime)->sub(new DateInterval("P1D"))->format("c"),
        "uniqueIds" => [
            "imdb" => "tt0088763"
        ]
    ],
    [
        "event" => "end",
        "mediaType" => "movie",
        "uniqueIds" => [
            "tmdb" => "348"
        ]
    ]
];

$client = new Client([
    "base_uri" => "http://localhost:8080",
    RequestOptions::AUTH => ["sample", "test"]
]);

foreach ($scrobbles as $index => $scrobble) {
    $response = $client->post("/api/scrobble", [
        RequestOptions::JSON => $scrobble
    ]);

    printf("[%d] %d: %s\n", $index, $response->getStatusCode(), $response->getBody()->getContents());
}
