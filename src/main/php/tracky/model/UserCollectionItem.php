<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;
use tracky\datetime\DateTime;
use tracky\orm\UserCollectionItemRepository;
use tracky\UserCollectionItemType;

#[ORM\Entity(repositoryClass: UserCollectionItemRepository::class)]
#[ORM\Table(name: "usercollectionitems")]
#[ORM\UniqueConstraint(name: "unique_collection_type_item", columns: ["collection", "type", "item"])]
class UserCollectionItem extends BaseEntity
{
    #[ORM\ManyToOne(targetEntity: UserCollection::class)]
    #[ORM\JoinColumn(name: "collection", referencedColumnName: "id", nullable: false)]
    private UserCollection $collection;

    #[ORM\Column(name: "type", enumType: UserCollectionItemType::class, type: "string", columnDefinition: "ENUM('show', 'season', 'episode', 'movie', 'movieset') NOT NULL")]
    protected UserCollectionItemType $type;

    #[ORM\Column(name: "item", type: "integer", nullable: false)]
    protected int $item;

    #[ORM\Column(name: "addedAt", type: "datetime", nullable: false)]
    protected DateTime $addedAt;

    protected ?BaseEntity $resolvedItem = null;

    public function getCollection(): UserCollection
    {
        return $this->collection;
    }

    public function setCollection(UserCollection $collection): self
    {
        $this->collection = $collection;
        return $this;
    }

    public function getType(): UserCollectionItemType
    {
        return $this->type;
    }

    public function setType(UserCollectionItemType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getItem(): int
    {
        return $this->item;
    }

    public function setItem(Show|Season|Episode|Movie|MovieSet $item): self
    {
        $this->item = $item->getId();
        $this->resolvedItem = $item;
        return $this;
    }

    public function getAddedAt(): DateTime
    {
        return $this->addedAt;
    }

    public function setAddedAt(DateTime $date): self
    {
        $this->addedAt = $date;
        return $this;
    }

    public function getResolvedItem(): BaseEntity
    {
        return $this->resolvedItem;
    }

    public function setResolvedItem(Show|Season|Episode|Movie|MovieSet $item): self
    {
        $this->resolvedItem = $item;
        return $this;
    }
}
