<?php
namespace tracky\scrobbler;

use Doctrine\ORM\EntityManagerInterface;
use tracky\dataprovider\TMDB;
use tracky\datetime\DateTime;
use tracky\model\EpisodeView;
use tracky\model\Movie;
use tracky\model\MovieView;
use tracky\model\ScrobbleQueue;
use tracky\model\Show;
use tracky\model\User;
use tracky\orm\MovieRepository;
use tracky\orm\ShowRepository;
use UnexpectedValueException;

class Scrobbler
{
    public function __construct(
        private readonly ShowRepository         $showRepository,
        private readonly MovieRepository        $movieRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly TMDB                   $tmdb,
        private readonly bool                   $useQueue = false,
    )
    {
    }

    public function cacheOrAddView(array $json, DateTime $dateTime, User $user): string
    {
        if ($this->useQueue) {
            return $this->queueView($json, $dateTime, $user);
        } else {
            return $this->addView($json, $dateTime, $user);
        }
    }

    public function queueView(array $json, DateTime $dateTime, User $user): string
    {
        $cache = new ScrobbleQueue;
        $cache->setJson($json);
        $cache->setDateTime($dateTime);
        $cache->setUser($user);

        $this->entityManager->persist($cache);
        $this->entityManager->flush();

        return "View added to queue";
    }

    public function addView(array $json, DateTime $dateTime, User $user): string
    {
        if (!isset($json["mediaType"])) {
            throw new UnexpectedValueException("Missing media type");
        }

        switch (strtolower($json["mediaType"])) {
            case "episode":
                $this->addEpisodeView($json, $dateTime, $user);
                return "Episode view added to database";
            case "movie":
                $this->addMovieView($json, $dateTime, $user);
                return "Movie view added to database";
            default:
                throw new UnexpectedValueException(sprintf("Invalid media type: %s", $json["mediaType"]));
        }
    }

    public function addViewFromQueue(ScrobbleQueue $scrobbleQueue): string
    {
        return $this->addView($scrobbleQueue->getJson(), $scrobbleQueue->getDateTime(), $scrobbleQueue->getUser());
    }

    private function addEpisodeView(array $json, DateTime $dateTime, User $user): void
    {
        $seasonNumber = $json["season"] ?? null;
        $episodeNumber = $json["episode"] ?? null;

        if ($seasonNumber === null) {
            throw new UnexpectedValueException("Missing season number");
        }

        if ($episodeNumber === null) {
            throw new UnexpectedValueException("Missing episode number");
        }

        $tmdbId = $this->getTmdbId($json["uniqueIds"] ?? [], "tv");
        if ($tmdbId === null) {
            throw new UnexpectedValueException("Unable to get TMDB ID");
        }

        $show = $this->showRepository->findOneBy(["tmdbId" => $tmdbId]);
        if ($show === null) {
            $show = new Show;
            $show->setTmdbId($tmdbId);
            $show->fetchTMDBData($this->tmdb);

            $this->entityManager->persist($show);
        }

        $season = $show->getSeason($seasonNumber);
        if ($season === null) {
            throw new UnexpectedValueException("Unknown season");
        }

        $episode = $season->getEpisode($episodeNumber);
        if ($episode === null) {
            throw new UnexpectedValueException("Unknown episode");
        }

        $episodeView = new EpisodeView;
        $episodeView->setEpisode($episode);
        $episodeView->setUser($user);
        $episodeView->setDateTime($dateTime);

        $this->entityManager->persist($episodeView);
        $this->entityManager->flush();
    }

    private function addMovieView(array $json, DateTime $dateTime, User $user): void
    {
        $tmdbId = $this->getTmdbId($json["uniqueIds"] ?? [], "movie");
        if ($tmdbId === null) {
            throw new UnexpectedValueException("Unable to get TMDB ID");
        }

        $movie = $this->movieRepository->findOneBy(["tmdbId" => $tmdbId]);
        if ($movie === null) {
            $movie = new Movie;
            $movie->setTmdbId($tmdbId);
            $movie->fetchTMDBData($this->tmdb);

            $this->entityManager->persist($movie);
        }

        $movieView = new MovieView;
        $movieView->setMovie($movie);
        $movieView->setUser($user);
        $movieView->setDateTime($dateTime);

        $this->entityManager->persist($movieView);
        $this->entityManager->flush();
    }

    private function getTmdbId(array $uniqueIds, string $expectedMediaType): ?int
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

            $tmdbId = $this->tmdb->getTmdbIdFromExternalId($externalSource, $uniqueId, $expectedMediaType);
            if ($tmdbId !== null) {
                return $tmdbId;
            }
        }

        return null;
    }
}