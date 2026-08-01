<?php
namespace tracky\model\traits;

use Doctrine\ORM\Mapping as ORM;

trait Runtime
{
    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $runtime;

    public function getRuntime(): int
    {
        return $this->runtime ?? 0;
    }

    public function setRuntime(?int $runtime): self
    {
        $this->runtime = $runtime;
        return $this;
    }
}
