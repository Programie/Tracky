<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;
use tracky\model\traits\DataProvider;
use tracky\model\traits\Plot;
use tracky\model\traits\PosterImage;
use tracky\orm\MovieSetRepository;

#[ORM\Entity(repositoryClass: MovieSetRepository::class)]
#[ORM\Table(name: "moviesets")]
class MovieSet extends BaseEntity
{
    use DataProvider;
    use Plot;
    use PosterImage;

    #[ORM\Column(type: "string")]
    private string $title;

    #[ORM\OneToMany(mappedBy: "movieSet", targetEntity: Movie::class, cascade: ["persist"])]
    #[ORM\OrderBy(["year" => "ASC"])]
    private mixed $movies = [];

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): MovieSet
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return Movie[]
     */
    public function getMovies(): mixed
    {
        return $this->movies;
    }

    /**
     * @param Movie[] $movies
     * @return MovieSet
     */
    public function setMovies(array $movies): MovieSet
    {
        $this->movies = $movies;
        return $this;
    }

    public function addMovie(Movie $movie): MovieSet
    {
        $this->movies[] = $movie;
        return $this;
    }

    public function getRuntime(): int
    {
        $runtime = 0;

        foreach ($this->getMovies() as $movie) {
            $runtime += $movie->getRuntime();
        }

        return $runtime;
    }
}
