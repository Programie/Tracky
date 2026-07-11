<?php
namespace tracky\settings\type;

use Symfony\Component\HttpFoundation\InputBag;
use tracky\settings\SettingName;

class Checkbox extends BaseType
{
    public function __construct(
        protected readonly SettingName $name,
        protected readonly string $label,
        /**
         * @var array<string, string>
         */
        protected readonly array $options,
        /**
         * @var string[]
         */
        protected readonly array $default = [],
    )
    {
    }

    public function getType(): string
    {
        return "checkbox";
    }

    /**
     * @var string[]
     */
    public function getValue(): array
    {
        $value = $this->getSetting()?->getValue() ?? null;

        if ($value === null) {
            return $this->default;
        } else {
            return array_unique(array_filter(explode(",", $value)));
        }
    }

    /**
     * @var array<string, string>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function isDefault(InputBag $inputBag): bool
    {
        $value = $this->getValueFromInputBag($inputBag);
        return empty(array_diff(array_merge($value, $this->default), array_intersect($value, $this->default)));
    }

    public function getSettingValueFromInputBag(InputBag $inputBag): string
    {
        return implode(",", $this->getValueFromInputBag($inputBag));
    }

    private function getValueFromInputBag(InputBag $inputBag): array
    {
        return array_unique(array_filter($inputBag->all($this->getName())));
    }
}
