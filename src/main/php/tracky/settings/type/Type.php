<?php
namespace tracky\settings\type;

use Symfony\Component\HttpFoundation\InputBag;
use tracky\model\Setting;

interface Type
{
    public function getType(): string;
    public function getName(): string;
    public function getSetting(): ?Setting;
    public function setSetting(?Setting $setting): void;
    public function getLabel(): string;
    public function getValue();
    public function isValid(InputBag $inputBag): bool;
    public function isSavable(): bool;
    public function getSettingValueFromInputBag(InputBag $inputBag): ?string;
}
