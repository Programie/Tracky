<?php
namespace tracky\settings\type;

use Symfony\Component\HttpFoundation\InputBag;
use tracky\model\UserSetting;

interface Type
{
    public function getType(): string;
    public function getName(): string;
    public function getSetting(): ?UserSetting;
    public function setSetting(?UserSetting $setting): void;
    public function getLabel(): string;
    public function getValue();
    public function isValid(InputBag $inputBag): bool;
    public function isSavable(): bool;
    public function getSettingValueFromInputBag(InputBag $inputBag): ?string;
}
