<?php
namespace tracky\orm;

use Doctrine\Persistence\ManagerRegistry;
use tracky\model\ScrobbleQueueItem;

class ScrobbleQueueItemRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScrobbleQueueItem::class);
    }
}
