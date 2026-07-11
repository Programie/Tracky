<?php
namespace tracky\settings\type;

use Exception;

class Section extends BaseType
{
    public function getType(): string
    {
        return "section";
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
