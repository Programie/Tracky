<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;
use tracky\datetime\DateTime;
use tracky\orm\UserCollectionRepository;

#[ORM\Entity(repositoryClass: UserCollectionRepository::class)]
#[ORM\Table(name: "usercollections")]
class UserCollection extends BaseEntity
{
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "user", referencedColumnName: "id", nullable: false)]
    protected User $user;

    #[ORM\Column(type: "string", nullable: false)]
    private string $name;

    #[ORM\Column(name: "createdAt", type: "datetime", nullable: false)]
    private DateTime $createdAt;

    #[ORM\OneToMany(mappedBy: "collection", targetEntity: UserCollectionItem::class, cascade: ["persist"])]
    private mixed $items = [];

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $date): self
    {
        $this->createdAt = $date;
        return $this;
    }

    /**
     * @return UserCollectionItem[]
     */
    public function getItems(): mixed
    {
        return $this->items;
    }

    public function setItems(mixed $items): self
    {
        $this->items = $items;
        return $this;
    }

    public function addItem(UserCollectionItem $item): self
    {
        $this->items[] = $item;
        return $this;
    }
}
