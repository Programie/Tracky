<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;
use tracky\datetime\DateTime;
use tracky\orm\ScrobbleQueueItemRepository;

#[ORM\Entity(repositoryClass: ScrobbleQueueItemRepository::class)]
#[ORM\Table(name: "scrobblequeue")]
class ScrobbleQueueItem extends BaseEntity
{
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "user", referencedColumnName: "id")]
    private User $user;

    #[ORM\Column(type: "string")]
    private string $json;

    #[ORM\Column(name: "dateTime", type: "datetime")]
    private DateTime $dateTime;

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): ScrobbleQueueItem
    {
        $this->user = $user;
        return $this;
    }

    public function getJson(): array
    {
        return json_decode($this->json, true);
    }

    public function setJson(array $json): ScrobbleQueueItem
    {
        $this->json = json_encode($json);
        return $this;
    }

    public function getDateTime(): DateTime
    {
        return $this->dateTime;
    }

    public function setDateTime(DateTime $dateTime): ScrobbleQueueItem
    {
        $this->dateTime = $dateTime;
        return $this;
    }
}