<?php
namespace tracky\settings\type;

use Exception;
use Symfony\Component\HttpFoundation\InputBag;

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

    public function isDefault(InputBag $inputBag): bool
    {
        throw new Exception("Not implemented");
    }
}
