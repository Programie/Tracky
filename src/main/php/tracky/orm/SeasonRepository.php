<?php
namespace tracky\orm;

use Doctrine\Persistence\ManagerRegistry;
use tracky\model\Season;

class SeasonRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Season::class);
    }
}
