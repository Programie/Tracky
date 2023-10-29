<?php
namespace tracky\orm;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use tracky\model\Show;

class ShowRepository extends ServiceEntityRepository
{
    use SearchTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Show::class);
    }
}