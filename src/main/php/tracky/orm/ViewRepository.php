<?php
namespace tracky\orm;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use tracky\datetime\DateRange;
use tracky\model\ViewEntry;

class ViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, string $entityClass = ViewEntry::class)
    {
        parent::__construct($registry, $entityClass);
    }

    private function getQueryBuilder(string $select, array $criteria, ?array $orderBy = null, $limit = null, $offset = null, ?string $type = null, ?DateRange $dateRange = null): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder("view")->select($select);

        foreach ($criteria as $key => $value) {
            $queryBuilder->andWhere(sprintf("view.%s = :%s", $key, $key));
            $queryBuilder->setParameter(sprintf(":%s", $key), $value);
        }

        if ($dateRange !== null) {
            $startDate = clone $dateRange->getStartDate()->toDateTime();
            $endDate = clone $dateRange->getEndDate()->toDateTime();

            $startDate->setTime(0, 0, 0);
            $endDate->setTime(23,59, 59);

            $queryBuilder->andWhere($queryBuilder->expr()->between("view.dateTime", ":dateRangeStart", ":dateRangeEnd"));
            $queryBuilder->setParameter(":dateRangeStart", $startDate->toUtc()->formatForDB());
            $queryBuilder->setParameter(":dateRangeEnd", $endDate->toUtc()->formatForDB());
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

        return $queryBuilder;
    }

    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null, ?string $type = null, ?DateRange $dateRange = null): array
    {
        $queryBuilder = $this->getQueryBuilder("view", $criteria, $orderBy, $limit, $offset, $type, $dateRange);

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

    public function count(array $criteria = [], ?string $type = null, ?DateRange $dateRange = null): int
    {
        $queryBuilder = $this->getQueryBuilder(select: "count(view.id)", criteria: $criteria, type: $type, dateRange: $dateRange);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getPaged(array $criteria, int $page, int $perPage, ?string $type = null, ?DateRange $dateRange = null)
    {
        $offset = ($page - 1) * $perPage;

        return $this->findBy($criteria, ["dateTime" => "desc"], $perPage, $offset, $type, $dateRange);
    }
}
