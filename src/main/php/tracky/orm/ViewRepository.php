<?php
namespace tracky\orm;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use tracky\datetime\DateRange;
use tracky\model\User;
use tracky\model\View;
use tracky\ViewType;
use tracky\watchstats\WatchStatsCollection;

class ViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, View::class);
    }

    private function getQueryBuilder(string $select, array $criteria, ?array $orderBy = null, $limit = null, $offset = null, ?ViewType $type = null, ?DateRange $dateRange = null): QueryBuilder
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
                ->andWhere("view.type = :type")
                ->setParameter(":type", $type->value);
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

    /**
     * @return View[]
     */
    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null, ?ViewType $type = null, ?DateRange $dateRange = null): array
    {
        $queryBuilder = $this->getQueryBuilder("view", $criteria, $orderBy, $limit, $offset, $type, $dateRange);

        return $queryBuilder->getQuery()->getResult();
    }

    public function findOneBy(array $criteria, ?array $orderBy = null, ?ViewType $type = null): ?View
    {
        $items = $this->findBy($criteria, $orderBy, 1, type: $type);

        if (empty($items)) {
            return null;
        }

        return $items[0];
    }

    /**
     * @return int[]
     */
    public function getItemIdsBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null, ?ViewType $type = null, ?DateRange $dateRange = null): array
    {
        $queryBuilder = $this->getQueryBuilder("view", $criteria, $orderBy, $limit, $offset, $type, $dateRange);

        $ids = [];

        foreach ($queryBuilder->getQuery()->getResult() as $view) {
            $ids[] = $view->getItem();
        }

        return $ids;
    }

    public function count(array $criteria = [], ?ViewType $type = null, ?DateRange $dateRange = null): int
    {
        $queryBuilder = $this->getQueryBuilder(select: "count(view.id)", criteria: $criteria, type: $type, dateRange: $dateRange);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getPaged(array $criteria, int $page, int $perPage, ?ViewType $type = null, ?DateRange $dateRange = null)
    {
        $offset = ($page - 1) * $perPage;

        return $this->findBy($criteria, ["dateTime" => "desc"], $perPage, $offset, $type, $dateRange);
    }

    public function getWatchStatsForUser(User $user, ViewType $viewType): WatchStatsCollection
    {
        $rows = $this->createQueryBuilder("view")
            ->select("
                view.item,
                COUNT(view.id) AS watchCount,
                MAX(view.dateTime) AS lastWatched
            ")
            ->where("view.user = :user")
            ->andWhere("view.type = :type")
            ->groupBy("view.item")
            ->setParameter("user", $user)
            ->setParameter("type", $viewType->value)
            ->getQuery()
            ->getArrayResult();

        return WatchStatsCollection::fromQueryRows($rows);
    }
}
