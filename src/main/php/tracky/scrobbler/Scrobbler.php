<?php
namespace tracky\scrobbler;

use Doctrine\ORM\EntityManagerInterface;
use tracky\DataCreator;
use tracky\datetime\DateTime;
use tracky\model\EpisodeView;
use tracky\model\MovieView;
use tracky\model\ScrobbleQueue;
use tracky\model\User;
use UnexpectedValueException;

class Scrobbler
{
    public function __construct(
        private readonly DataCreator            $dataCreator,
        private readonly EntityManagerInterface $entityManager,
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

        $episode = $this->dataCreator->getOrCreateEpisode($json["uniqueIds"] ?? [], $seasonNumber, $episodeNumber);

        $episodeView = new EpisodeView;
        $episodeView->setEpisode($episode);
        $episodeView->setUser($user);
        $episodeView->setDateTime($dateTime);

        $this->entityManager->persist($episodeView);
        $this->entityManager->flush();
    }

    private function addMovieView(array $json, DateTime $dateTime, User $user): void
    {
        $movie = $this->dataCreator->getOrCreateMovie($json["uniqueIds"] ?? []);

        $movieView = new MovieView;
        $movieView->setMovie($movie);
        $movieView->setUser($user);
        $movieView->setDateTime($dateTime);

        $this->entityManager->persist($movieView);
        $this->entityManager->flush();
    }
}