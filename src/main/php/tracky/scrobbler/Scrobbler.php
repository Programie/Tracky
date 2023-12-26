<?php
namespace tracky\scrobbler;

use Doctrine\ORM\EntityManagerInterface;
use tracky\dataprovider\Helper;
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
        private readonly Helper                 $dataProviderHelper,
        private readonly ShowRepository         $showRepository,
        private readonly MovieRepository        $movieRepository,
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
        if (!isset($json["mediaType"])) {
            throw new UnexpectedValueException("Missing media type");
        }

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

        list($dataProvider, $providerId) = $this->getDataProviderFromUniqueIds(Helper::TYPE_SHOW, $json);

        $show = $this->showRepository->findOneBy([$dataProvider->getIdFieldName() => $providerId]);
        if ($show === null) {
            $show = new Show;
            $dataProvider->setIdForShow($show, $providerId);
            $dataProvider->fetchShow($show, false);

            $this->entityManager->persist($show);
        }

        $created = false;
        $season = $show->getOrCreateSeason($seasonNumber, $created);
        if ($created) {
            $dataProvider->fetchSeason($season, false);

            $this->entityManager->persist($season);
        }

        $created = false;
        $episode = $season->getOrCreateEpisode($episodeNumber, $created);
        if ($created) {
            $dataProvider->fetchEpisode($episode);

            $this->entityManager->persist($episode);
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
        list($dataProvider, $providerId) = $this->getDataProviderFromUniqueIds(Helper::TYPE_MOVIE, $json);

        $movie = $this->movieRepository->findOneBy([$dataProvider->getIdFieldName() => $providerId]);
        if ($movie === null) {
            $movie = new Movie;
            $dataProvider->setIdForMovie($movie, $providerId);
            $dataProvider->fetchMovie($movie);

            $this->entityManager->persist($movie);
        }

        $movieView = new MovieView;
        $movieView->setMovie($movie);
        $movieView->setUser($user);
        $movieView->setDateTime($dateTime);

        $this->entityManager->persist($movieView);
        $this->entityManager->flush();
    }

    private function getDataProviderFromUniqueIds(string $type, array $json): array
    {
        $dataProvider = $this->dataProviderHelper->getProviderByType($type);

        $providerId = $dataProvider->getIdFromUniqueIds($json["uniqueIds"] ?? []);
        if ($providerId === null) {
            throw new UnexpectedValueException("Unable to get ID from data provider");
        }

        return [$dataProvider, $providerId];
    }
}