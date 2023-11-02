<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;
use tracky\orm\MovieViewRepository;

#[ORM\Entity(repositoryClass: MovieViewRepository::class)]
#[ORM\Table(name: "movieviews")]
class MovieView extends ViewEntry
{
    #[ORM\ManyToOne(targetEntity: Movie::class)]
    #[ORM\JoinColumn(name: "movie", referencedColumnName: "id")]
    private Movie $movie;

    public function getMovie(): Movie
    {
        return $this->movie;
    }

    public function setMovie(Movie $movie): MovieView
    {
        $this->movie = $movie;
        return $this;
    }
}