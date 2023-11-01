<?php
namespace tracky\model\traits;

use Doctrine\ORM\Mapping as ORM;

trait TMDB
{
    #[ORM\Column(name: "tmdbId", type: "integer")]
    private ?int $tmdbId;

    #[ORM\Column(name: "language", type: "string")]
    private ?string $language = null;

    public function getTmdbId(): ?int
    {
        return $this->tmdbId;
    }

    public function setTmdbId(?int $tmdbId): self
    {
        $this->tmdbId = $tmdbId;
        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): self
    {
        $this->language = $language;
        return $this;
    }
}