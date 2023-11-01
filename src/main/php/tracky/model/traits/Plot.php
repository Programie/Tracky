<?php
namespace tracky\model\traits;

use Doctrine\ORM\Mapping as ORM;

trait Plot
{
    #[ORM\Column(type: "text")]
    private ?string $plot;

    public function getPlot(): ?string
    {
        return $this->plot;
    }

    public function setPlot(?string $plot): self
    {
        if ($plot !== null) {
            $plot = trim($plot);
            if ($plot === "") {
                $plot = null;
            }
        }

        $this->plot = $plot;
        return $this;
    }
}