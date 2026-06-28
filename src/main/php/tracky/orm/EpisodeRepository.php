<?php
namespace tracky\orm;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use tracky\model\Episode;

class EpisodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Episode::class);
    }

    public function search(string $query, ?int $showId = null)
    {
        $queryBuilder = $this->createQueryBuilder("episode");

        if ($showId !== null) {
            $queryBuilder->leftJoin("episode.season", "season");
            $queryBuilder->leftJoin("season.show", "show");

            $queryBuilder->andWhere("show.id = :showId");
            $queryBuilder->setParameter("showId", $showId);
        }

        $queryBuilder->andWhere($queryBuilder->expr()->orX(
            "episode.title LIKE :query",
            "episode.plot LIKE :query"
        ));

        return $queryBuilder
            ->setParameter(":query", "%" . $query . "%")
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Episode[]
     */
    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder("episode");

        $queryBuilder->where($queryBuilder->expr()->in("episode.id", $ids));

        return $queryBuilder->getQuery()->getResult();
    }
}
