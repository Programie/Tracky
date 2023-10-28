<?php
namespace tracky\orm;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use tracky\model\MovieView;
use tracky\model\User;

class MovieViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MovieView::class);
    }

    public function getPaged(User $user, int $page, int $perPage)
    {
        $offset = ($page - 1) * $perPage;

        return $this->findBy(["user" => $user], ["dateTime" => "desc"], $perPage, $offset);
    }
}