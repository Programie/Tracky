<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;
use tracky\datetime\DateTime;
use tracky\orm\ViewRepository;
use tracky\ViewType;

#[ORM\Entity(repositoryClass: ViewRepository::class)]
#[ORM\Table(name: "views")]
class View extends BaseEntity
{
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "user", referencedColumnName: "id")]
    protected User $user;

    #[ORM\Column(name: "datetime", type: "datetime")]
    protected DateTime $dateTime;

    #[ORM\Column(name: "item", type: "integer")]
    protected int $item;

    #[ORM\Column(name: "type", enumType: ViewType::class, type: "string", columnDefinition: "ENUM('episode', 'movie')")]
    protected ViewType $type;

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

    public function getItem(): int
    {
        return $this->item;
    }

    public function setItem(Episode|Movie $item): self
    {
        $this->item = $item->getId();
        return $this;
    }

    public function getType(): ViewType
    {
        return $this->type;
    }

    public function setType(ViewType $type): self
    {
        $this->type = $type;
        return $this;
    }
}
