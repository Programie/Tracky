<?php
namespace tracky\model\traits;

use Doctrine\ORM\Mapping as ORM;

trait PosterImage
{
    #[ORM\Column(name: "posterImageUrl", type: "string")]
    private ?string $posterImageUrl = null;

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