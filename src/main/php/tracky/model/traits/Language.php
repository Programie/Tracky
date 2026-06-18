<?php
namespace tracky\model\traits;

use Doctrine\ORM\Mapping as ORM;

trait Language
{
    #[ORM\Column(name: "language", type: "string", nullable: true)]
    private ?string $language = null;

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
