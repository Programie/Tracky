<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;
use tracky\model\traits\Plot;
use tracky\model\traits\PosterImage;
use tracky\model\traits\DataProvider;
use tracky\model\traits\Runtime;
use tracky\orm\MovieRepository;
use tracky\ViewType;

#[ORM\Entity(repositoryClass: MovieRepository::class)]
#[ORM\Table(
    name: "movies",
    indexes: [
        new ORM\Index(
            name: "idx_movies_tmdbid",
            columns: ["tmdbId"]
        ),
        new ORM\Index(
            name: "idx_movies_tvdbid",
            columns: ["tvdbId"]
        )
    ]
)]
class Movie extends BaseEntity
{
    use DataProvider;
    use Plot;
    use PosterImage;
    use Runtime;

    #[ORM\Column(type: "string")]
    private string $title;

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $tagline;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $year;

    #[ORM\ManyToOne(targetEntity: MovieSet::class, cascade: ["persist"])]
    #[ORM\JoinColumn(name: "movieset", referencedColumnName: "id")]
    private ?MovieSet $movieSet;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): Movie
    {
        $this->title = $title;
        return $this;
    }

    public function getTagline(): ?string
    {
        return $this->tagline;
    }

    public function setTagline(?string $tagline): Movie
    {
        $this->tagline = $tagline;
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

    public function getMovieSet(): ?MovieSet
    {
        return $this->movieSet;
    }

    public function setMovieSet(?MovieSet $movieSet): Movie
    {
        $this->movieSet = $movieSet;
        return $this;
    }

    public function getViewType(): ViewType
    {
        return ViewType::MOVIE;
    }
}
