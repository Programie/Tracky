<?php
namespace tracky\orm;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

abstract class AbstractRepository extends ServiceEntityRepository
{
    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder("entity");

        $queryBuilder->where($queryBuilder->expr()->in("entity.id", $ids));

        return $queryBuilder->getQuery()->getResult();
    }
}
