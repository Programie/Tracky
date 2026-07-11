<?php
namespace tracky\settings\type;

use Exception;

class Section extends BaseType
{
    public function __construct(
        protected readonly string $label
    )
    {
    }

    public function getType(): string
    {
        return "section";
    }

    public function getName(): string
    {
        return spl_object_hash($this);
    }

    public function getValue()
    {
        throw new Exception("Not implemented");
    }

    public function isSavable(): bool
    {
        return false;
    }
}
