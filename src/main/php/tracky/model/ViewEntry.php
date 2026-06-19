<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;
use tracky\datetime\DateTime;
use tracky\ViewType;

#[ORM\Entity]
#[ORM\Table(name: "views")]
#[ORM\InheritanceType("SINGLE_TABLE")]
#[ORM\DiscriminatorColumn(name: "type", enumType: ViewType::class, type: "string", columnDefinition: "ENUM('episode', 'movie')")]
#[ORM\DiscriminatorMap([ViewType::EPISODE->value => EpisodeView::class, ViewType::MOVIE->value => MovieView::class])]
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

    abstract public function getType(): ViewType;
}
