<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;
use tracky\model\User;

#[ORM\Entity]
#[ORM\Table(name: "settings")]
class Setting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private User $user;

    #[ORM\Column(type: "string", length: 255)]
    private string $setting;

    #[ORM\Column(type: "string", length: 255)]
    private string $value;

    public function getId(): int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }
    public function getSetting(): string { return $this->setting; }
    public function setSetting(string $setting): self { $this->setting = $setting; return $this; }
    public function getValue(): string { return $this->value; }
    public function setValue(string $value): self { $this->value = $value; return $this; }
}
