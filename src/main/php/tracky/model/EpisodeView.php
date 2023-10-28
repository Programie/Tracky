<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "EpisodeViewRepository")]
#[ORM\Table(name: "episodeviews")]
class EpisodeView extends ViewEntry
{
    #[ORM\OneToOne(targetEntity: "Episode")]
    #[ORM\JoinColumn(name: "episode", referencedColumnName: "id")]
    private Episode $episode;

    public function getEpisode(): Episode
    {
        return $this->episode;
    }

    public function setEpisode(Episode $episode): EpisodeView
    {
        $this->episode = $episode;
        return $this;
    }
}