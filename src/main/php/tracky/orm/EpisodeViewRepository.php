<?php
namespace tracky\orm;

use Doctrine\Persistence\ManagerRegistry;
use tracky\model\EpisodeView;

class EpisodeViewRepository extends ViewRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EpisodeView::class);
    }
}