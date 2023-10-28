<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;

trait TMDBTrait
{
    #[ORM\Column(name: "tmdbId", type: "integer")]
    private ?int $tmdbId;

    #[ORM\Column(name: "posterImageUrl", type: "string")]
    private ?string $posterImageUrl;

    public function getTmdbId(): ?int
    {
        return $this->tmdbId;
    }

    public function setTmdbId(?int $tmdbId): self
    {
        $this->tmdbId = $tmdbId;
        return $this;
    }

    public function getPosterImageUrl(): ?string
    {
        return $this->posterImageUrl;
    }

    public function setPosterImageUrl(?string $posterImageUrl): self
    {
        $this->posterImageUrl = $posterImageUrl;
        return $this;
    }
}