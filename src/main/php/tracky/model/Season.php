<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;
use tracky\dataprovider\TMDB;
use tracky\datetime\Date;
use tracky\model\traits\PosterImage;

#[ORM\Entity(repositoryClass: "tracky\orm\SeasonRepository")]
#[ORM\Table(name: "seasons")]
class Season extends BaseEntity
{
    use PosterImage;

    #[ORM\OneToOne(targetEntity: "Show")]
    #[ORM\JoinColumn(name: "`show`", referencedColumnName: "id")]
    private Show $show;

    #[ORM\Column(type: "integer")]
    private int $number;

    #[ORM\OneToMany(mappedBy: "season", targetEntity: "Episode", cascade: ["persist"])]
    private mixed $episodes = [];

    public function getShow(): Show
    {
        return $this->show;
    }

    public function setShow(Show $show): Season
    {
        $this->show = $show;
        return $this;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): Season
    {
        $this->number = $number;
        return $this;
    }

    /**
     * @return Episode[]
     */
    public function getEpisodes(): mixed
    {
        return $this->episodes;
    }

    /**
     * @param Episode[] $episodes
     * @return $this
     */
    public function setEpisodes(mixed $episodes): Season
    {
        $this->episodes = $episodes;
        return $this;
    }

    public function addEpisode(Episode $episode): Season
    {
        $this->episodes[] = $episode;
        return $this;
    }

    public function getEpisode(int $episode): ?Episode
    {
        foreach ($this->getEpisodes() as $episodeEntry) {
            if ($episodeEntry->getNumber() === $episode) {
                return $episodeEntry;
            }
        }

        return null;
    }

    public function fetchTMDBData(TMDB $tmdb): bool
    {
        $tmdbId = $this->getShow()->getTmdbId();
        if ($tmdbId === null) {
            return false;
        }

        foreach ($tmdb->getShowEpisodes($tmdbId, $this->getNumber(), $this->getShow()->getLanguage()) as $episodeData) {
            $episodeNumber = $episodeData["episode_number"];

            $episode = $this->getEpisode($episodeNumber);
            if ($episode === null) {
                $episode = new Episode;
                $episode->setSeason($this);
                $episode->setNumber($episodeNumber);
                $this->addEpisode($episode);
            }

            $episode->setTitle($episodeData["name"]);
            $episode->setFirstAired(new Date($episodeData["air_date"]));
            $episode->setPosterImageUrl($episodeData["posterImageUrl"]);
        }

        return true;
    }
}