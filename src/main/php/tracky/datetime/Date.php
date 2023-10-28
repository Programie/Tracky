<?php
namespace tracky\datetime;

use DateInterval;
use DateTime;
use JsonSerializable;

class Date extends DateTime implements JsonSerializable
{
    public function getPreviousWeek(): Date
    {
        $date = clone $this;
        $date->sub(new DateInterval("P1W"));
        return $date;
    }

    public function getNextWeek(): Date
    {
        $date = clone $this;
        $date->add(new DateInterval("P1W"));
        return $date;
    }

    public function getStartOfWeek(): Date
    {
        $date = clone $this;
        $currentWeekDay = $date->format("N");
        $date->sub(new DateInterval(sprintf("P%dD", $currentWeekDay - 1)));
        return $date;
    }

    public function getEndOfWeek(): Date
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