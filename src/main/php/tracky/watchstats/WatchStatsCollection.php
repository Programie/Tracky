<?php
namespace tracky\watchstats;

use tracky\datetime\DateTime;
use tracky\model\BaseEntity;
use tracky\model\Episode;
use tracky\model\Movie;

class WatchStatsCollection
{
    /**
     * @var array<int, ItemWatchStats>
     */
    private array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public static function fromQueryRows(array $rows)
    {
        $data = [];

        foreach ($rows as $row) {
            $data[(int) $row["item"]] = new ItemWatchStats(count: (int) $row["watchCount"], lastWatched: new DateTime($row["lastWatched"]));
        }

        return new static($data);
    }

    public function getStatsForItem(Episode|Movie|int $item): ?ItemWatchStats
    {
        if ($item instanceof BaseEntity) {
            $id = $item->getId();
        } else {
            $id = $item;
        }

        return $this->data[$id] ?? null;
    }
}
