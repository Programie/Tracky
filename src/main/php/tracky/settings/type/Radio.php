<?php
namespace tracky\settings\type;

class Radio extends BaseType
{
    public function __construct(
        protected readonly string $name,
        protected readonly string $label,
        protected readonly string $default,
        /**
         * @var array<string, string>
         */
        protected readonly array $options
    )
    {
    }

    public function getType(): string
    {
        return "radio";
    }

    public function getValue(): string
    {
        return $this->getSetting()?->getValue() ?? $this->default;
    }

    /**
     * @var array<string, string>
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
