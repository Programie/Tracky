<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;
use tracky\orm\UserRepository;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: "users")]
class User extends BaseEntity
{
    #[ORM\Column(type: "string")]
    private string $username;

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): User
    {
        $this->username = $username;
        return $this;
    }
}