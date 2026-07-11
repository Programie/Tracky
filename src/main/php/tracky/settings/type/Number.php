<?php
namespace tracky\settings\type;

use Symfony\Component\HttpFoundation\InputBag;
use tracky\settings\SettingName;

class Number extends BaseType
{
    public function __construct(
        protected readonly SettingName $name,
        protected readonly string $label,
        protected readonly int $default,
        protected readonly int $min = 0,
        protected readonly int $max = PHP_INT_MAX,
        protected readonly ?string $suffixLabel = null
    )
    {
    }

    public function getType(): string
    {
        return "number";
    }

    public function getValue(): int
    {
        return (int)($this->getSetting()?->getValue() ?? $this->default);
    }

    public function getMin(): int
    {
        return $this->min;
    }

    public function getMax(): int
    {
        return $this->max;
    }

    public function getSuffixLabel(): ?string
    {
        return $this->suffixLabel;
    }

    public function isValid(InputBag $inputBag): bool
    {
        $value = (int) $inputBag->getDigits($this->getName());

        return $value >= $this->min and $value <= $this->max;
    }

    public function isDefault(InputBag $inputBag): bool
    {
        return ((int) $inputBag->getDigits($this->getName())) === $this->default;
    }
}
