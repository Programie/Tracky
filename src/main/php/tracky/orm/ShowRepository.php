<?php
namespace tracky\orm;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use tracky\model\Episode;
use tracky\model\Season;
use tracky\model\Show;

class ShowRepository extends ServiceEntityRepository
{
    use SearchTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Show::class);
    }

    /**
     * @return Show[]
     */
    public function findAllWithEpisodesAndViews(int $userId)
    {
        $query = $this->getEntityManager()->createQuery("
            SELECT show, season, episode, view
            FROM tracky\model\Show show
            LEFT JOIN show.seasons season
            LEFT JOIN season.episodes episode
            LEFT JOIN episode.views view
            LEFT JOIN view.user user
            WHERE user.id IS NULL OR user.id = :userId
            ORDER BY show.title ASC
        ");

        $query->setParameter("userId", $userId);

        $query->setFetchMode(Show::class, "seasons", ClassMetadataInfo::FETCH_EAGER);
        $query->setFetchMode(Season::class, "episodes", ClassMetadataInfo::FETCH_EAGER);
        $query->setFetchMode(Episode::class, "views", ClassMetadataInfo::FETCH_EAGER);

        return $query->getResult();
    }
}