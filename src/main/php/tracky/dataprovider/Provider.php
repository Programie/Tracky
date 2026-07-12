<?php
namespace tracky\dataprovider;

use tracky\model\Episode;
use tracky\model\Movie;
use tracky\model\MovieSet;
use tracky\model\Season;
use tracky\model\Show;

interface Provider
{
    public function getIdFieldName(): string;

    public function setIdForEntity(Show|Movie|MovieSet $entity, mixed $id): void;

    public function getIdFromUniqueIds(array $uniqueIds): mixed;

    public function fetchShow(Show $show, bool $createSeasonsAndEpisodes): bool;

    public function fetchSeason(Season $season, bool $createEpisodes): bool;

    public function fetchEpisode(Episode $episode): bool;

    public function fetchMovie(Movie $movie): bool;

    public function fetchMovieSet(MovieSet $movieSet): bool;

    public function searchShow(string $query, ?int $year): array;

    public function searchMovie(string $query, ?int $year): array;
}
