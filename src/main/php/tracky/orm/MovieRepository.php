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
    public function findAllWithViews(int $userId): array
    {
        $query = $this->getEntityManager()->createQuery("
            SELECT movie, view
            FROM tracky\model\Movie movie
            LEFT JOIN movie.views view
            LEFT JOIN view.user user
            WHERE user.id IS NULL OR user.id = :userId
        ");

        $query->setParameter("userId", $userId);

        return $query->getResult();
    }
}
