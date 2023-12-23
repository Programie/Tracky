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

    #[ORM\Column(name: "dataProvider", type: "string", columnDefinition: "ENUM('tmdb', 'tvdb')")]
    private ?string $dataProvider;

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

    public function getDataProvider(): ?string
    {
        return $this->dataProvider;
    }

    public function setDataProvider(?string $dataProvider): self
    {
        $this->dataProvider = $dataProvider;
        return $this;
    }
}