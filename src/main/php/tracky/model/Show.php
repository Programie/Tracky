<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;
use tracky\dataprovider\TMDB;
use tracky\model\traits\PosterImage;
use tracky\model\traits\TMDB as TMDBTrait;
use tracky\orm\ShowRepository;

#[ORM\Entity(repositoryClass: ShowRepository::class)]
#[ORM\Table(name: "shows")]
class Show extends BaseEntity
{
    use TMDBTrait;
    use PosterImage;

    #[ORM\Column(type: "string")]
    private string $title;

    #[ORM\OneToMany(mappedBy: "show", targetEntity: Season::class, cascade: ["persist"])]
    #[ORM\OrderBy(["number" => "ASC"])]
    private mixed $seasons = [];

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): Show
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return Season[]
     */
    public function getSeasons(): mixed
    {
        return $this->seasons;
    }

    /**
     * @param Season[] $seasons
     * @return $this
     */
    public function setSeasons(mixed $seasons): Show
    {
        $this->seasons = $seasons;
        return $this;
    }

    public function addSeason(Season $season): Show
    {
        $this->seasons[] = $season;
        return $this;
    }

    public function getSeason(int $season): ?Season
    {
        foreach ($this->getSeasons() as $seasonEntry) {
            if ($seasonEntry->getNumber() === $season) {
                return $seasonEntry;
            }
        }

        return null;
    }

    public function fetchTMDBData(TMDB $tmdb): bool
    {
        $tmdbId = $this->getTmdbId();
        if ($tmdbId === null) {
            return false;
        }

        $showData = $tmdb->getShowData($tmdbId, $this->getLanguage());
        $this->setTitle($showData["title"]);
        $this->setPosterImageUrl($showData["posterImageUrl"]);

        foreach ($showData["seasons"] as $seasonData) {
            $seasonNumber = $seasonData["season_number"];

            $season = $this->getSeason($seasonNumber);
            if ($season === null) {
                $season = new Season;
                $season->setShow($this);
                $season->setNumber($seasonNumber);
                $this->addSeason($season);
            }

            $season->setPosterImageUrl($seasonData["posterImageUrl"]);

            $season->fetchTMDBData($tmdb);
        }

        return true;
    }
}