<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;
use tracky\datetime\DateTime;
use tracky\orm\ViewRepository;

#[ORM\Entity(repositoryClass: ViewRepository::class)]
#[ORM\Table(name: "views")]
#[ORM\InheritanceType("SINGLE_TABLE")]
#[ORM\DiscriminatorColumn(name: "type", type: "string", columnDefinition: "ENUM('episode', 'movie')")]
#[ORM\DiscriminatorMap(["episode" => EpisodeView::class, "movie" => MovieView::class])]
abstract class ViewEntry extends BaseEntity
{
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "user", referencedColumnName: "id")]
    protected User $user;

    #[ORM\Column(name: "datetime", type: "datetime")]
    protected DateTime $dateTime;

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

    abstract public function getType(): string;
}