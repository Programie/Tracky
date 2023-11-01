<?php
namespace tracky\model\traits;

use Doctrine\ORM\Mapping as ORM;

trait TMDB
{
    use Language;

    #[ORM\Column(name: "tmdbId", type: "integer")]
    private ?int $tmdbId;

    public function getTmdbId(): ?int
    {
        return $this->tmdbId;
    }

    public function setTmdbId(?int $tmdbId): self
    {
        $this->tmdbId = $tmdbId;
        return $this;
    }
}