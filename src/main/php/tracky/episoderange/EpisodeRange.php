<?php
namespace tracky\episoderange;

use tracky\model\Episode;

class EpisodeRange
{
    public function __construct(
        private readonly int $startSeason,
        private readonly ?int $startEpisode,
        private readonly int $endSeason,
        private readonly ?int $endEpisode,
        private readonly bool $isExclusion,
    )
    {
    }

    public static function fromString(string $range): ?EpisodeRange
    {
        $isExclusion = str_starts_with($range, "!");
        if ($isExclusion) {
            $range = substr($range, 1);
        }

        // Single episode
        if (preg_match("/^S(\d+)E(\d+)$/", $range, $matches)) {
            return new static((int) $matches[1], (int) $matches[2], (int) $matches[1], (int) $matches[2], $isExclusion);
        }

        // Whole single season
        if (preg_match("/^S(\d+)$/", $range, $matches)) {
            return new static((int) $matches[1], null, (int) $matches[1], null, $isExclusion);
        }

        // Whole multiple seasons
        if (preg_match("/^S(\d+)-S(\d+)$/", $range, $matches)) {
            return new static((int) $matches[1], null, (int) $matches[2], null, $isExclusion);
        }

        // Episode range of a single season
        if (preg_match("/^S(\d+)E(\d+)-E?(\d+)$/", $range, $matches)) {
            return new static((int) $matches[1], (int) $matches[2], (int) $matches[1], (int) $matches[3], $isExclusion);
        }

        // Multiple seasons starting with a specific episode and ending with the last episode
        if (preg_match("/^S(\d+)E(\d+)-S(\d+)$/", $range, $matches)) {
            return new static((int) $matches[1], (int) $matches[2], (int) $matches[3], null, $isExclusion);
        }

        // Multiple seasons starting with the first episode and ending with a specific episode
        if (preg_match("/^S(\d+)-S(\d+)E(\d+)$/", $range, $matches)) {
            return new static((int) $matches[1], null, (int) $matches[2], (int) $matches[3], $isExclusion);
        }

        // Multiple seasons starting with a specific episode and ending with a specific episode
        if (preg_match("/^S(\d+)E(\d+)-S(\d+)E(\d+)$/", $range, $matches)) {
            return new static((int) $matches[1], (int) $matches[2], (int) $matches[3], (int) $matches[4], $isExclusion);
        }

        return null;
    }

    public function toString(): string
    {
        $parts = [];

        if ($this->isExclusion) {
            $parts[] = "!";
        }

        $parts[] = sprintf("S%02d", $this->startSeason);

        if ($this->startEpisode !== null) {
            $parts[] = sprintf("E%02d", $this->startEpisode);
        }

        $parts[] = "-";

        if ($this->endSeason !== $this->startSeason) {
            $parts[] = sprintf("S%02d", $this->endSeason);
        }

        if ($this->endEpisode !== null and ($this->startSeason !== $this->endSeason or $this->startEpisode !== $this->endEpisode)) {
            $parts[] = sprintf("E%02d", $this->endEpisode);
        }

        return trim(implode("", $parts), "-");
    }

    public function isExclusion(): bool
    {
        return $this->isExclusion;
    }

    public function isInRange(Episode $episode): bool
    {
        $seasonNumber = $episode->getSeason()->getNumber();
        $episodeNumber = $episode->getNumber();

        // Season is out of range
        if ($seasonNumber < $this->startSeason or $seasonNumber > $this->endSeason) {
            return false;
        }

        // Season is the first season but episode is before start episode
        if ($seasonNumber === $this->startSeason and $this->startEpisode !== null and $episodeNumber < $this->startEpisode) {
            return false;
        }

        // Season is the last season but episode is after end episode
        if ($seasonNumber === $this->endSeason and $this->endEpisode !== null and $episodeNumber > $this->endEpisode) {
            return false;
        }

        return true;
    }
}
