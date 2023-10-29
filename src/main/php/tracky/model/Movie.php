<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "tracky\orm\MovieRepository")]
#[ORM\Table(name: "movies")]
class Movie
{
    use TMDBTrait;

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: "string")]
    private string $title;

    #[ORM\Column(type: "integer")]
    private ?int $year;

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): Movie
    {
        $this->title = $title;
        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): Movie
    {
        $this->year = $year;
        return $this;
    }
}