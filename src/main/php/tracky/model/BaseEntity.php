<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;

abstract class BaseEntity
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue]
    protected int $id;

    public function getId(): int
    {
        return $this->id;
    }

    public function getClassName(): string
    {
        $parts = explode("\\", get_class($this));

        return end($parts);
    }
}
