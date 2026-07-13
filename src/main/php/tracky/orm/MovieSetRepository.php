<?php
namespace tracky\orm;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use tracky\model\MovieSet;

class MovieSetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MovieSet::class);
    }

    /**
     * @return MovieSet[]
     */
    public function findAllWithMovies(): array
    {
        $query = $this->getEntityManager()->createQuery("
            SELECT movieset, movie
            FROM tracky\model\MovieSet movieset
            LEFT JOIN movieset.movies movie
        ");

        $query->setFetchMode(MovieSet::class, "movies", ClassMetadataInfo::FETCH_EAGER);

        return $query->getResult();
    }
}
