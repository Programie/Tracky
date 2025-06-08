<?php
namespace tracky\datetime;

use DateTimeZone;

class DateTime extends Date
{
    public static function fromUtc(string $datetime): static
    {
        return new static($datetime, new DateTimeZone("UTC"));
    }

    public function toUtc(): static
    {
        $utcDate = clone $this;
        $utcDate->setTimezone(new DateTimeZone("UTC"));
        return $utcDate;
    }

    public function toDate(): Date
    {
        $date = clone $this;
        $date->setTimezone((new static)->getTimezone());
        return new Date($date->format("Y-m-d"));
    }

    public function formatForDB(): string
    {
        return $this->format("Y-m-d H:i:s");
    }

    public function formatForJs(): string
    {
        return $this->format("c");
    }

    public function formatForKey(): string
    {
        return $this->format("Y-m-d H:i:s");
    }

    public function formatForDisplay(): string
    {
        return $this->format("d.m.Y H:i:s");
    }
}
