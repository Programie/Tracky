<?php
namespace tracky\orm;

use Doctrine\Persistence\ManagerRegistry;
use tracky\model\MovieView;

class MovieViewRepository extends ViewRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MovieView::class);
    }
}