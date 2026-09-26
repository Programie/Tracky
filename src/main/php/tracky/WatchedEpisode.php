<?php
namespace tracky;

use tracky\model\Episode;
use tracky\watchstats\ItemWatchStats;

class WatchedEpisode
{
    public function __construct(
        public readonly Episode $episode,
        public readonly ItemWatchStats $itemWatchStats
    )
    {
    }
}
