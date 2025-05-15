<?php
namespace tracky\orm;

trait SearchTrait
{
    public function search(string $query, bool $searchInPlot = true)
    {
        $queryBuilder = $this->createQueryBuilder("entry")
            ->select("entry")
            ->where("entry.title LIKE :query");

        if ($searchInPlot) {
            $queryBuilder->orWhere("entry.plot LIKE :query");
        }

        return $queryBuilder
            ->setParameter(":query", "%" . $query . "%")
            ->getQuery()
            ->getResult();
    }
}
