<?php
namespace tracky\model;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "tracky\orm\EpisodeRepository")]
#[ORM\Table(name: "episodes")]
class Episode
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\OneToOne(targetEntity: "Show")]
    #[ORM\JoinColumn(name: "show", referencedColumnName: "id")]
    private Show $show;

    #[ORM\Column(type: "integer")]
    private string $season;

    #[ORM\Column(type: "integer")]
    private string $episode;

    #[ORM\Column(type: "string")]
    private string $title;

    #[ORM\Column(name: "firstAired", type: "date")]
    private DateTime $firstAired;

    public function getId(): int
    {
        return $this->id;
    }

    public function getShow(): Show
    {
        return $this->show;
    }

    public function setShow(Show $show): Episode
    {
        $this->show = $show;
        return $this;
    }

    public function getSeason(): string
    {
        return $this->season;
    }

    public function setSeason(string $season): Episode
    {
        $this->season = $season;
        return $this;
    }

    public function getEpisode(): string
    {
        return $this->episode;
    }

    public function setEpisode(string $episode): Episode
    {
        $this->episode = $episode;
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