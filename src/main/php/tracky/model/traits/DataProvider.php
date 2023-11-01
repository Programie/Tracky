<?php
namespace tracky\model\traits;

use Doctrine\ORM\Mapping as ORM;

trait DataProvider
{
    use Language;

    #[ORM\Column(name: "tmdbId", type: "integer")]
    private ?int $tmdbId;

    #[ORM\Column(name: "tvdbId", type: "integer")]
    private ?int $tvdbId;

    public function getTmdbId(): ?int
    {
        return $this->tmdbId;
    }

    public function setTmdbId(?int $tmdbId): self
    {
        $this->tmdbId = $tmdbId;
        return $this;
    }

    public function getTvdbId(): ?int
    {
        return $this->tvdbId;
    }

    public function setTvdbId(?int $tvdbId): self
    {
        $this->tvdbId = $tvdbId;
        return $this;
    }
}