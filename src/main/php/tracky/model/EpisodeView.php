<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;
use tracky\orm\EpisodeViewRepository;

#[ORM\Entity(repositoryClass: EpisodeViewRepository::class)]
class EpisodeView extends ViewEntry
{
    #[ORM\ManyToOne(targetEntity: Episode::class)]
    #[ORM\JoinColumn(name: "item", referencedColumnName: "id")]
    protected mixed $item;

    public function getEpisode(): Episode
    {
        return $this->item;
    }

    public function setEpisode(Episode $episode): EpisodeView
    {
        $this->item = $episode;
        return $this;
    }

    public function getType(): string
    {
        return "episode";
    }
}