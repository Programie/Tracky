<?php
namespace tracky\settings\type;

use Symfony\Component\HttpFoundation\InputBag;

class Text extends BaseType
{
    public function __construct(
        protected readonly string $name,
        protected readonly string $label,
        protected readonly string $default,
        protected readonly string $placeholder,
        protected readonly ?string $regex = null,
        protected readonly bool $required = false
    )
    {
    }

    public function getType(): string
    {
        return "text";
    }

    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }

    public function getRegex(): string
    {
        return $this->regex;
    }

    public function getValue(): string
    {
        return $this->getSetting()?->getValue() ?? $this->default;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function isValid(InputBag $inputBag): bool
    {
        $value = trim($inputBag->get($this->getName()));

        // Check if value has been specified if required
        if ($this->required and ($value === null or $value === "")) {
            return false;
        }

        // Check whether value matches the regex
        if ($this->regex !== null) {
            if ($value === null or !preg_match(sprintf("/%s/", $this->regex), $value)) {
                return false;
            }
        }

        return true;
    }
}
