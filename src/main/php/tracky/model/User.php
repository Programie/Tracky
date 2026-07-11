<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use tracky\orm\UserRepository;
use tracky\settings\UserSettings;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: "users")]
class User extends BaseEntity implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Column(type: "string")]
    private string $username;

    #[ORM\Column(type: "string")]
    private string $password;

    /**
     * @var UserSetting[]
     */
    #[ORM\OneToMany(mappedBy: "user", targetEntity: UserSetting::class)]
    private mixed $settings = [];

    private ?UserSettings $userSettings = null;

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): User
    {
        $this->username = $username;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): User
    {
        $this->password = $password;
        return $this;
    }

    public function getRoles(): array
    {
        return ["ROLE_USER"];
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getSettings(): UserSettings
    {
        if ($this->userSettings !== null) {
            return $this->userSettings;
        }

        $this->userSettings = new UserSettings;

        foreach ($this->settings as $setting) {
            $option = $this->userSettings->getOption($setting->getName());
            if ($option === null) {
                continue;
            }

            $option->setSetting($setting);
        }

        return $this->userSettings;
    }
}
