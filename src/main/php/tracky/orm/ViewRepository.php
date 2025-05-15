<?php
namespace tracky\orm;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use tracky\model\ViewEntry;

class ViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, string $entityClass = ViewEntry::class)
    {
        parent::__construct($registry, $entityClass);
    }

    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null, ?string $type = null): array
    {
        $queryBuilder = $this->createQueryBuilder("view")->select("view");

        foreach ($criteria as $key => $value) {
            $queryBuilder->andWhere(sprintf("view.%s = :%s", $key, $key));
            $queryBuilder->setParameter(sprintf(":%s", $key), $value);
        }

        if ($type !== null) {
            $queryBuilder
                ->andWhere("view INSTANCE OF :type")
                ->setParameter(":type", $type);
        }

        if ($orderBy !== null) {
            foreach ($orderBy as $sort => $order) {
                $queryBuilder->addOrderBy(sprintf("view.%s", $sort), $order);
            }
        }

        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }

        if ($offset !== null) {
            $queryBuilder->setFirstResult($offset);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function findOneBy(array $criteria, ?array $orderBy = null, ?string $type = null): ?object
    {
        $items = $this->findBy($criteria, $orderBy, 1, type: $type);

        if (empty($items)) {
            return null;
        }

        return $items[0];
    }

    public function count(array $criteria = [], ?string $type = null): int
    {
        $queryBuilder = $this->createQueryBuilder("view")->select("count(view.id)");

        foreach ($criteria as $key => $value) {
            $queryBuilder->andWhere(sprintf("view.%s = :%s", $key, $key));
            $queryBuilder->setParameter(sprintf(":%s", $key), $value);
        }

        if ($type !== null) {
            $queryBuilder
                ->andWhere("view INSTANCE OF :type")
                ->setParameter(":type", $type);
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getPaged(array $criteria, int $page, int $perPage, ?string $type = null)
    {
        $offset = ($page - 1) * $perPage;

        return $this->findBy($criteria, ["dateTime" => "desc"], $perPage, $offset, $type);
    }
}
