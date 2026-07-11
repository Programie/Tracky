<?php
namespace tracky\settings\type;

use Symfony\Component\HttpFoundation\InputBag;
use tracky\model\UserSetting;

abstract class BaseType implements Type
{
    private ?UserSetting $setting = null;

    public function __construct(
        protected readonly string $name,
        protected readonly string $label
    )
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getSetting(): ?UserSetting
    {
        return $this->setting;
    }

    public function setSetting(?UserSetting $setting): void
    {
        $this->setting = $setting;
    }

    public function isValid(InputBag $inputBag): bool
    {
        return true;
    }

    public function isSavable(): bool
    {
        return true;
    }

    public function getSettingValueFromInputBag(InputBag $inputBag): ?string
    {
        return $inputBag->get($this->getName());
    }
}
