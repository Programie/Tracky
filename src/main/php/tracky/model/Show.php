<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;
use tracky\datetime\Date;
use tracky\datetime\DateTime;
use tracky\ImageFetcher;
use tracky\model\traits\PosterImage;
use tracky\model\traits\DataProvider;
use tracky\orm\ShowRepository;
use tracky\watchstats\WatchStatsProvider;

#[ORM\Entity(repositoryClass: ShowRepository::class)]
#[ORM\Table(name: "shows")]
class Show extends BaseEntity
{
    const STATUS_UPCOMING = "upcoming";
    const STATUS_CONTINUING = "continuing";
    const STATUS_ENDED = "ended";

    use DataProvider;
    use PosterImage;

    #[ORM\Column(type: "string")]
    private string $title;

    #[ORM\Column(type: "string", columnDefinition: "ENUM('upcoming', 'continuing', 'ended')", nullable: true)]
    private ?string $status;

    #[ORM\Column(name: "lastUpdate", type: "datetime", nullable: true)]
    private ?DateTime $lastUpdate;

    #[ORM\OneToMany(mappedBy: "show", targetEntity: Season::class, cascade: ["persist"])]
    #[ORM\OrderBy(["number" => "ASC"])]
    private mixed $seasons = [];

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): Show
    {
        $this->title = $title;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): Show
    {
        $this->status = $status;
        return $this;
    }

    public function setLastUpdate(?DateTime $lastUpdate): Show
    {
        $this->lastUpdate = $lastUpdate;
        return $this;
    }

    public function getLastUpdate(): ?DateTime
    {
        return $this->lastUpdate;
    }

    public function needsUpdate(int $maxAge): bool
    {
        // Update show if never updated before
        if ($this->getLastUpdate() === null) {
            return true;
        }

        // Do not update show if ended
        if ($this->getStatus() === self::STATUS_ENDED) {
            return false;
        }

        // Update show if max age passed
        $now = new Date;
        $diff = $now->getTimestamp() - $this->getLastUpdate()->getTimestamp();

        return $diff >= $maxAge;
    }

    /**
     * @return Season[]
     */
    public function getSeasons(): mixed
    {
        return $this->seasons;
    }

    /**
     * @param Season[] $seasons
     * @return $this
     */
    public function setSeasons(mixed $seasons): Show
    {
        $this->seasons = $seasons;
        return $this;
    }

    public function addSeason(Season $season): Show
    {
        $this->seasons[] = $season;
        return $this;
    }

    public function getSeason(int $season): ?Season
    {
        foreach ($this->getSeasons() as $seasonEntry) {
            if ($seasonEntry->getNumber() === $season) {
                return $seasonEntry;
            }
        }

        return null;
    }

    public function getOrCreateSeason(int $seasonNumber, &$created = false): Season
    {
        $season = $this->getSeason($seasonNumber);
        if ($season === null) {
            $season = new Season;
            $season->setShow($this);
            $season->setNumber($seasonNumber);

            $this->addSeason($season);

            $created = true;
        }

        return $season;
    }

    public function fetchPosterImages(ImageFetcher $imageFetcher, bool $includeSeasons, bool $includeEpisodes): void
    {
        $this->fetchPosterImage($imageFetcher);

        if ($includeSeasons) {
            foreach ($this->getSeasons() as $season) {
                $season->fetchPosterImages($imageFetcher, $includeEpisodes);
            }
        }
    }

    public function getTotalEpisodes(): int
    {
        $totalEpisodes = 0;

        foreach ($this->getSeasons() as $season) {
            $totalEpisodes += count($season->getEpisodes());
        }

        return $totalEpisodes;
    }

    public function getRandomEpisodes(int $count): array
    {
        $allEpisodes = [];
        $randomEpisodes = [];

        foreach ($this->getSeasons() as $season) {
            foreach ($season->getEpisodes() as $episode) {
                $allEpisodes[] = $episode;
            }
        }

        foreach (array_rand($allEpisodes, $count) as $index) {
            $randomEpisodes[] = $allEpisodes[$index];
        }

        return $randomEpisodes;
    }

    /**
     * @return list<array{Episode, ItemWatchStats}>
     */
    public function getLatestWatchedEpisodes(WatchStatsProvider $watchStatsProvider, int $count, bool $includeWatchStats = false): array
    {
        $episodes = $this->getWatchedEpisodesSortedByLastWatched($watchStatsProvider);

        return array_slice($episodes, 0, $count);
    }

    /**
     * @return list<array{Episode, ItemWatchStats}>
     */
    public function getMostOrLeastWatchedEpisodes(WatchStatsProvider $watchStatsProvider, int $count, bool $leastWatched): array
    {
        $episodes = $this->getWatchedEpisodes($watchStatsProvider);

        usort($episodes, function($item1, $item2) {
            $item1WatchStats = $item1[1];
            $item2WatchStats = $item2[1];

            return $item2WatchStats->getCount() <=> $item1WatchStats->getCount();
        });

        if ($leastWatched) {
            $episodes = array_reverse($episodes);
        }

        return array_slice($episodes, 0, $count);
    }

    /**
     * @return list<array{Episode, ItemWatchStats}>
     */
    public function getWatchedEpisodesSortedByLastWatched(WatchStatsProvider $watchStatsProvider): array
    {
        $episodes = $this->getWatchedEpisodes($watchStatsProvider);

        usort($episodes, function ($item1, $item2) {
            $item1WatchStats = $item1[1];
            $item2WatchStats = $item2[1];

            return $item2WatchStats->getLastWatched() <=> $item1WatchStats->getLastWatched();
        });

        return $episodes;
    }

    /**
     * @return list<array{Episode, ItemWatchStats}>
     */
    public function getWatchedEpisodes(WatchStatsProvider $watchStatsProvider): array
    {
        $allEpisodes = [];

        foreach ($this->getSeasons() as $season) {
            foreach ($season->getEpisodes() as $episode) {
                $itemWatchStats = $watchStatsProvider->getItemStats($episode);
                if ($itemWatchStats === null or !$itemWatchStats->getCount()) {
                    continue;
                }

                $allEpisodes[] = [$episode, $itemWatchStats];
            }
        }

        return $allEpisodes;
    }

    /**
     * @return Episode[]
     */
    public function getUnwatchedEpisodes(WatchStatsProvider $watchStatsProvider): array
    {
        $unwatchedEpisodes = [];

        foreach ($this->getSeasons() as $season) {
            foreach ($season->getEpisodes() as $episode) {
                $itemWatchStats = $watchStatsProvider->getItemStats($episode);
                if ($itemWatchStats !== null and $itemWatchStats->getCount()) {
                    continue;
                }

                $unwatchedEpisodes[] = $episode;
            }
        }

        return $unwatchedEpisodes;
    }
}
