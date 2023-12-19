<?php
namespace tracky\dataprovider;

use tracky\model\Episode;
use tracky\model\Movie;
use tracky\model\Season;
use tracky\model\Show;

interface Provider
{
    public function getIdFieldName(): string;

    public function setIdForShow(Show $show, mixed $id): void;

    public function setIdForMovie(Movie $movie, mixed $id): void;

    public function getIdFromUniqueIds(array $uniqueIds): mixed;

    public function fetchShow(Show $show, bool $createSeasonsAndEpisodes): bool;

    public function fetchSeason(Season $season, bool $createEpisodes): bool;

    public function fetchEpisode(Episode $episode): bool;

    public function fetchMovie(Movie $movie): bool;

    public function searchShow(string $query, ?int $year): array;

    public function searchMovie(string $query, ?int $year): array;
}