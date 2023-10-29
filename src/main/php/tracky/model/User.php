<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "tracky\orm\UserRepository")]
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