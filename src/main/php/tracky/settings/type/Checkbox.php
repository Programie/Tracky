<?php
namespace tracky\settings\type;

use Symfony\Component\HttpFoundation\InputBag;

class Checkbox extends BaseType
{
    public function __construct(
        protected readonly string $name,
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

    public function getSettingValueFromInputBag(InputBag $inputBag): string
    {
        return implode(",", array_unique(array_filter($inputBag->all($this->getName()))));
    }
}
