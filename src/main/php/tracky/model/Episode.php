<?php
namespace tracky\model;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use tracky\datetime\Date;
use tracky\model\traits\Plot;
use tracky\model\traits\PosterImage;
use tracky\model\traits\Runtime;
use tracky\orm\EpisodeRepository;

#[ORM\Entity(repositoryClass: EpisodeRepository::class)]
#[ORM\Table(name: "episodes")]
class Episode extends BaseEntity
{
    use Plot;
    use PosterImage;
    use Runtime;

    #[ORM\ManyToOne(targetEntity: Season::class)]
    #[ORM\JoinColumn(name: "season", referencedColumnName: "id")]
    private Season $season;

    #[ORM\Column(type: "integer")]
    private int $number;

    #[ORM\Column(type: "string")]
    private string $title;

    #[ORM\Column(name: "firstAired", type: "date")]
    private ?Date $firstAired;

    #[ORM\OneToMany(mappedBy: "item", targetEntity: EpisodeView::class)]
    #[ORM\OrderBy(["dateTime" => "ASC"])]
    private mixed $views;

    public function getSeason(): Season
    {
        return $this->season;
    }

    public function setSeason(Season $season): Episode
    {
        $this->season = $season;
        return $this;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): Episode
    {
        $this->number = $number;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): Episode
    {
        $this->title = $title;
        return $this;
    }

    public function getFirstAired(): ?Date
    {
        return $this->firstAired;
    }

    public function setFirstAired(?Date $firstAired): Episode
    {
        $this->firstAired = $firstAired;
        return $this;
    }

    public function getViewsForUser(User $user)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq("user", $user));

        return $this->views->matching($criteria);
    }

    public function getPreviousEpisode(): ?Episode
    {
        $season = $this->getSeason();

        $episode = $season->getEpisode($this->getNumber() - 1);
        if ($episode !== null) {
            return $episode;
        }

        $episode = $season->getPreviousSeason()?->getEpisodes()->last();
        if ($episode !== false) {
            return $episode;
        }

        return null;
    }

    public function getNextEpisode(): ?Episode
    {
        $season = $this->getSeason();

        $episode = $season->getEpisode($this->getNumber() + 1);
        if ($episode !== null) {
            return $episode;
        }

        $episode = $season->getNextSeason()?->getEpisodes()->first();
        if ($episode !== false) {
            return $episode;
        }

        return null;
    }

    public function __toString(): string
    {
        return sprintf("%dx%d %s", $this->getSeason()->getNumber(), $this->getNumber(), $this->getTitle());
    }
}
