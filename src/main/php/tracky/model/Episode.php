<?php
namespace tracky\model;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use tracky\model\traits\PosterImage;

#[ORM\Entity(repositoryClass: "tracky\orm\EpisodeRepository")]
#[ORM\Table(name: "episodes")]
class Episode extends BaseEntity
{
    use PosterImage;

    #[ORM\OneToOne(targetEntity: "Season")]
    #[ORM\JoinColumn(name: "season", referencedColumnName: "id")]
    private Season $season;

    #[ORM\Column(type: "integer")]
    private int $number;

    #[ORM\Column(type: "string")]
    private string $title;

    #[ORM\Column(name: "firstAired", type: "date")]
    private DateTime $firstAired;

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

    public function getFirstAired(): DateTime
    {
        return $this->firstAired;
    }

    public function setFirstAired(DateTime $firstAired): Episode
    {
        $this->firstAired = $firstAired;
        return $this;
    }
}