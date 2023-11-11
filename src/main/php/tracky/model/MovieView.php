<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;
use tracky\orm\MovieViewRepository;

#[ORM\Entity(repositoryClass: MovieViewRepository::class)]
class MovieView extends ViewEntry
{
    #[ORM\ManyToOne(targetEntity: Movie::class)]
    #[ORM\JoinColumn(name: "item", referencedColumnName: "id")]
    protected mixed $item;

    public function getMovie(): Movie
    {
        return $this->item;
    }

    public function setMovie(Movie $movie): MovieView
    {
        $this->item = $movie;
        return $this;
    }

    public function getType(): string
    {
        return "movie";
    }
}