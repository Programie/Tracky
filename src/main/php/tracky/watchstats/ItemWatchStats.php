<?php
namespace tracky\watchstats;

use tracky\datetime\DateTime;

class ItemWatchStats
{
    public function __construct(
        private readonly int $count,
        private readonly DateTime $lastWatched
    ) {}

    public function getCount(): int
    {
        return $this->count;
    }

    public function getLastWatched(): DateTime
    {
        return $this->lastWatched;
    }
}
