<?php
namespace tracky\orm;

trait SearchTrait
{
    public function search(string $query)
    {
        return $this->createQueryBuilder("entry")
            ->select("entry")
            ->where("entry.title LIKE :query")
            ->setParameter(":query", "%" . $query . "%")
            ->getQuery()
            ->getResult();
    }
}