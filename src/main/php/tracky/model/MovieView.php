<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "MovieViewRepository")]
#[ORM\Table(name: "movieviews")]
class MovieView extends ViewEntry
{
    #[ORM\OneToOne(targetEntity: "Movie")]
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