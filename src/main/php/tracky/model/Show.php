<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "tracky\orm\ShowRepository")]
#[ORM\Table(name: "shows")]
class Show
{
    use TMDBTrait;

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: "string")]
    private string $title;

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): Show
    {
        $this->title = $title;
        return $this;
    }
}