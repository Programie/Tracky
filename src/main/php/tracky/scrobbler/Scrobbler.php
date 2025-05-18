<?php
namespace tracky\scrobbler;

use Doctrine\ORM\EntityManagerInterface;
use tracky\dataprovider\Helper;
use tracky\datetime\DateTime;
use tracky\model\Episode;
use tracky\model\EpisodeView;
use tracky\model\Movie;
use tracky\model\MovieView;
use tracky\model\ScrobbleQueueItem;
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
        private readonly NowWatchingHelper      $nowWatchingHelper,
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

        $cache = new ScrobbleQueueItem;
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

    public function addViewFromQueue(ScrobbleQueueItem $scrobbleQueue): string
    {
        return $this->addView($scrobbleQueue->getJson(), $scrobbleQueue->getDateTime(), $scrobbleQueue->getUser());
    }

    public function clearNowWatching(DateTime $dateTime, User $user)
    {
        $this->nowWatchingHelper->clear($dateTime, $user);

        return "Now watching cleared";
    }

    public function setNowWatching(array $json, DateTime $dateTime, User $user): string
    {
        if (!isset($json["mediaType"])) {
            throw new UnexpectedValueException("Missing media type");
        }

        switch (strtolower($json["mediaType"])) {
            case "episode":
                $this->nowWatchingHelper->store($json, $dateTime, $user);
                return "Episode set as now watching";
            case "movie":
                $this->nowWatchingHelper->store($json, $dateTime, $user);
                return "Movie set as now watching";
            default:
                throw new UnexpectedValueException(sprintf("Invalid media type: %s", $json["mediaType"]));
        }
    }

    public function getNowWatching(User $user): array|null
    {
        $json = $this->nowWatchingHelper->get($user);

        if ($json === null) {
            return null;
        }

        if (!isset($json["mediaType"])) {
            throw new UnexpectedValueException("Missing media type");
        }

        switch (strtolower($json["mediaType"])) {
            case "episode":
                $json["entry"] = $this->getEpisode($json);
                break;
            case "movie":
                $json["entry"] = $this->getMovie($json);
                break;
            default:
                throw new UnexpectedValueException(sprintf("Invalid media type: %s", $json["mediaType"]));
        }

        return $json;
    }

    private function addEpisodeView(array $json, DateTime $dateTime, User $user): void
    {
        $episode = $this->getEpisode($json);

        $episodeView = new EpisodeView;
        $episodeView->setEpisode($episode);
        $episodeView->setUser($user);
        $episodeView->setDateTime($dateTime);

        $this->entityManager->persist($episodeView);
        $this->entityManager->flush();
    }

    private function addMovieView(array $json, DateTime $dateTime, User $user): void
    {
        $movie = $this->getMovie($json);

        $movieView = new MovieView;
        $movieView->setMovie($movie);
        $movieView->setUser($user);
        $movieView->setDateTime($dateTime);

        $this->entityManager->persist($movieView);
        $this->entityManager->flush();
    }

    private function getEpisode(array $json): Episode
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
            $this->entityManager->flush();
        }

        $created = false;
        $season = $show->getOrCreateSeason($seasonNumber, $created);
        if ($created) {
            $dataProvider->fetchSeason($season, false);

            $this->entityManager->persist($season);
            $this->entityManager->flush();
        }

        $created = false;
        $episode = $season->getOrCreateEpisode($episodeNumber, $created);
        if ($created) {
            $dataProvider->fetchEpisode($episode);

            $this->entityManager->persist($episode);
            $this->entityManager->flush();
        }

        return $episode;
    }

    private function getMovie(array $json): Movie
    {
        list($dataProvider, $providerId) = $this->getDataProviderFromUniqueIds(Helper::TYPE_MOVIE, $json);

        $movie = $this->movieRepository->findOneBy([$dataProvider->getIdFieldName() => $providerId]);
        if ($movie === null) {
            $movie = new Movie;
            $dataProvider->setIdForMovie($movie, $providerId);
            $dataProvider->fetchMovie($movie);

            $this->entityManager->persist($movie);
            $this->entityManager->flush();
        }

        return $movie;
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
