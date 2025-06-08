<?php
namespace tracky\datetime;

use DateInterval;
use DateTime as BaseDateTime;
use JsonSerializable;

class Date extends BaseDateTime implements JsonSerializable
{
    public static function fromUtc(string $datetime): static
    {
        return new static($datetime);// No conversion as dates to not have a timezone
    }

    public function toUtc(): static
    {
        return clone $this;// No conversion as dates to not have a timezone
    }

    public function toDateTime(): DateTime
    {
        return new DateTime($this->format("Y-m-d"));
    }

    public function isInTheFuture(): bool
    {
        return $this > new static;
    }

    public function isInThePast(): bool
    {
        return $this < new static;
    }

    public function getPreviousWeek(): static
    {
        $date = clone $this;
        $date->sub(new DateInterval("P1W"));
        return $date;
    }

    public function getNextWeek(): static
    {
        $date = clone $this;
        $date->add(new DateInterval("P1W"));
        return $date;
    }

    public function getStartOfWeek(): static
    {
        $date = clone $this;
        $currentWeekDay = $date->format("N");
        $date->sub(new DateInterval(sprintf("P%dD", $currentWeekDay - 1)));
        return $date;
    }

    public function getEndOfWeek(): static
    {
        $date = clone $this;
        $currentWeekDay = $date->format("N");
        $date->add(new DateInterval(sprintf("P%dD", 7 - $currentWeekDay)));
        return $date;
    }

    public function formatForDB(): string
    {
        return $this->format("Y-m-d");
    }

    public function formatForUrl(): string
    {
        return $this->format("Y-m-d");
    }

    public function formatForKey(): string
    {
        return $this->format("Y-m-d");
    }

    public function formatForDisplay(): string
    {
        return $this->format("d.m.Y");
    }

    public function jsonSerialize(): string
    {
        return $this->format("c");
    }
}
