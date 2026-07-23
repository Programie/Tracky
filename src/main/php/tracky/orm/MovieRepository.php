<?php
namespace tracky\orm;

use Doctrine\Persistence\ManagerRegistry;
use tracky\model\Movie;

class MovieRepository extends AbstractRepository
{
    use SearchTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Movie::class);
    }
}
