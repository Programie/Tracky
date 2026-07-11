<?php
namespace tracky\settings\type;

use Symfony\Component\HttpFoundation\InputBag;

class Select extends BaseType
{
    public function __construct(
        protected readonly string $name,
        protected readonly string $label,
        protected readonly string $default,
        /**
         * @var array<string, string | null>
         */
        protected readonly array $options
    )
    {
    }

    public function getType(): string
    {
        return "select";
    }

    public function getValue(): string
    {
        return $this->getSetting()?->getValue() ?? $this->default;
    }

    /**
     * @var array<string, string | null>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function isDefault(InputBag $inputBag): bool
    {
        return $inputBag->get($this->getName()) === $this->default;
    }
}
