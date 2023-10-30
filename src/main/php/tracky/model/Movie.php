<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;
use tracky\dataprovider\TMDB;
use tracky\model\traits\PosterImage;
use tracky\model\traits\TMDB as TMDBTrait;
use tracky\orm\MovieRepository;

#[ORM\Entity(repositoryClass:MovieRepository::class)]
#[ORM\Table(name: "movies")]
class Movie extends BaseEntity
{
    use TMDBTrait;
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

    public function fetchTMDBData(TMDB $tmdb): bool
    {
        $tmdbId = $this->getTmdbId();
        if ($tmdbId === null) {
            return false;
        }

        $showData = $tmdb->getMovieData($tmdbId, $this->getLanguage());
        $this->setTitle($showData["title"]);
        $this->setPosterImageUrl($showData["posterImageUrl"]);

        return true;
    }
}