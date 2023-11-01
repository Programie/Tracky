<?php
namespace tracky;

use Doctrine\ORM\EntityManagerInterface;
use tracky\dataprovider\Provider;
use tracky\model\Episode;
use tracky\model\Movie;
use tracky\model\Season;
use tracky\model\Show;
use tracky\orm\MovieRepository;
use tracky\orm\ShowRepository;
use UnexpectedValueException;

class DataCreator
{
    public function __construct(
        private readonly Provider               $dataProvider,
        private readonly ShowRepository         $showRepository,
        private readonly MovieRepository        $movieRepository,
        private readonly EntityManagerInterface $entityManager
    )
    {
    }

    public function getOrCreateShow(array $uniqueIds, bool $createSeasonsAndEpisodes): Show
    {
        $providerId = $this->dataProvider->getIdFromUniqueIds($uniqueIds);
        if ($providerId === null) {
            throw new UnexpectedValueException("Unable to get ID from data provider");
        }

        $show = $this->showRepository->findOneBy([$this->dataProvider->getIdFieldName() => $providerId]);
        if ($show === null) {
            $show = new Show;
            $this->dataProvider->setIdForShow($show, $providerId);
            $this->dataProvider->fetchShow($show, $createSeasonsAndEpisodes);

            $this->entityManager->persist($show);
        }

        return $show;
    }

    public function getOrCreateSeason(array $uniqueIds, int $seasonNumber, bool $createEpisodes): Season
    {
        $show = $this->getOrCreateShow($uniqueIds, false);

        $created = false;
        $season = $show->getOrCreateSeason($seasonNumber, $created);
        if ($created) {
            $this->dataProvider->fetchSeason($season, $createEpisodes);

            $this->entityManager->persist($season);
        }

        return $season;
    }

    public function getOrCreateEpisode(array $uniqueIds, int $seasonNumber, int $episodeNumber): Episode
    {
        $season = $this->getOrCreateSeason($uniqueIds, $seasonNumber, false);

        $created = false;
        $episode = $season->getOrCreateEpisode($episodeNumber, $created);
        if ($created) {
            $this->dataProvider->fetchEpisode($episode);

            $this->entityManager->persist($episode);
        }

        return $episode;
    }

    public function getOrCreateMovie(array $uniqueIds): Movie
    {
        $providerId = $this->dataProvider->getIdFromUniqueIds($uniqueIds);
        if ($providerId === null) {
            throw new UnexpectedValueException("Unable to get ID from data provider");
        }

        $movie = $this->movieRepository->findOneBy([$this->dataProvider->getIdFieldName() => $providerId]);
        if ($movie === null) {
            $movie = new Movie;
            $this->dataProvider->setIdForMovie($movie, $providerId);
            $this->dataProvider->fetchMovie($movie);

            $this->entityManager->persist($movie);
        }

        return $movie;
    }
}