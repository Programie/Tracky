<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;
use tracky\orm\EpisodeViewRepository;

#[ORM\Entity(repositoryClass: EpisodeViewRepository::class)]
#[ORM\Table(name: "episodeviews")]
class EpisodeView extends ViewEntry
{
    #[ORM\ManyToOne(targetEntity: Episode::class)]
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