<?php
namespace tracky\episoderange;

use tracky\model\Episode;

class EpisodeRanges
{
    /**
     * @var EpisodeRange[]
     */
    private array $ranges = [];

    public static function fromString(string $string): ?EpisodeRanges
    {
        $ranges = new static;

        $string = trim($string);

        foreach (explode(",", $string) as $rangeString) {
            $rangeString = trim($rangeString);
            if ($rangeString === "") {
                continue;
            }

            $range = EpisodeRange::fromString($rangeString);
            if ($range === null) {
                continue;
            }

            $ranges->addRange($range);
        }

        if (!$ranges->hasRanges()) {
            return null;
        }

        return $ranges;
    }

    public function toString(): string
    {
        $parts = [];

        foreach ($this->ranges as $range) {
            $parts[] = $range->toString();
        }

        return implode(",", $parts);
    }

    public function addRange(EpisodeRange $range)
    {
        $this->ranges[] = $range;
    }

    public function hasRanges(): bool
    {
        return !empty($this->ranges);
    }

    public function isInRange(Episode $episode): bool
    {
        $inRange = false;

        foreach ($this->ranges as $range) {
            if (!$range->isInRange($episode)) {
                continue;
            }

            if ($range->isExclusion()) {
                return false;
            }

            $inRange = true;
        }

        return $inRange;
    }
}
