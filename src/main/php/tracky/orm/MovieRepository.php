<?php
namespace tracky\orm;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use tracky\model\Movie;

class MovieRepository extends ServiceEntityRepository
{
    use SearchTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Movie::class);
    }

    /**
     * @return Movie[]
     */
    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder("movie");

        $queryBuilder->where($queryBuilder->expr()->in("movie.id", $ids));

        return $queryBuilder->getQuery()->getResult();
    }
}
