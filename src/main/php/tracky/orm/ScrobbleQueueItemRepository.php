<?php
namespace tracky\orm;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use tracky\model\ScrobbleQueueItem;

class ScrobbleQueueItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScrobbleQueueItem::class);
    }
}