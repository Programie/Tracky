<?php
namespace tracky\model\traits;

use Doctrine\ORM\Mapping as ORM;
use tracky\ImageFetcher;

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

    public function fetchPosterImage(ImageFetcher $imageFetcher): ?string
    {
        $url = $this->getPosterImageUrl();
        if ($url === null) {
            return null;
        }

        return $imageFetcher->get($url);
    }
}