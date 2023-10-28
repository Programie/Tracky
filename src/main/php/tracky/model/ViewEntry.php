<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;
use tracky\datetime\DateTime;

abstract class ViewEntry
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue]
    protected int $id;

    #[ORM\OneToOne(targetEntity: "User")]
    #[ORM\JoinColumn(name: "user", referencedColumnName: "id")]
    protected User $user;

    #[ORM\Column(name: "dateTime", type: "datetime")]
    protected DateTime $dateTime;

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getDateTime(): DateTime
    {
        return $this->dateTime;
    }

    public function setDateTime(DateTime $dateTime): self
    {
        $this->dateTime = $dateTime;
        return $this;
    }

    public function getType(): ?string
    {
        return match (get_class($this)) {
            EpisodeView::class => "episode",
            MovieView::class => "movie",
            default => null,
        };
    }
}