<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;
use tracky\model\traits\Plot;
use tracky\model\traits\PosterImage;
use tracky\model\traits\TMDB as TMDBTrait;
use tracky\orm\MovieRepository;

#[ORM\Entity(repositoryClass: MovieRepository::class)]
#[ORM\Table(name: "movies")]
class Movie extends BaseEntity
{
    use TMDBTrait;
    use Plot;
    use PosterImage;

    #[ORM\Column(type: "string")]
    private string $title;

    #[ORM\Column(type: "integer")]
    private ?int $year;

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