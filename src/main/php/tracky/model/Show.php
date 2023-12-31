<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;
use tracky\datetime\Date;
use tracky\datetime\DateTime;
use tracky\ImageFetcher;
use tracky\model\traits\PosterImage;
use tracky\model\traits\DataProvider;
use tracky\orm\ShowRepository;

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

    #[ORM\Column(type: "string", columnDefinition: "ENUM('upcoming', 'continuing', 'ended')")]
    private ?string $status;

    #[ORM\Column(name: "lastUpdate", type: "datetime")]
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

    public function getLatestWatchedEpisodes(User $user, int $count): array
    {
        $allEpisodes = [];

        foreach ($this->getSeasons() as $season) {
            foreach ($season->getEpisodes() as $episode) {
                $views = $episode->getViewsForUser($user);
                $viewCount = count($views);
                if (!$viewCount) {
                    continue;
                }

                $allEpisodes[] = [$episode, $views->last()->getDateTime()->getTimestamp()];
            }
        }

        usort($allEpisodes, function ($item1, $item2) {
            list(, $item1Timestamp) = $item1;
            list(, $item2Timestamp) = $item2;


            if ($item1Timestamp === $item2Timestamp) {
                return 0;
            }

            return ($item1Timestamp > $item2Timestamp) ? -1 : 1;
        });

        foreach ($allEpisodes as &$item) {
            $item = $item[0];
        }

        return array_slice($allEpisodes, 0, $count);
    }

    public function getMostOrLeastWatchedEpisodes(User $user, int $count, bool $leastWatched = false): array
    {
        $allEpisodes = [];

        foreach ($this->getSeasons() as $season) {
            foreach ($season->getEpisodes() as $episode) {
                $views = $episode->getViewsForUser($user);
                $viewCount = count($views);
                if (!$viewCount) {
                    continue;
                }

                $allEpisodes[] = [$episode, $viewCount, $views->last()->getDateTime()->getTimestamp()];
            }
        }

        usort($allEpisodes, function ($item1, $item2) {
            list(, $item1Count, $item1Timestamp) = $item1;
            list(, $item2Count, $item2Timestamp) = $item2;


            if ($item1Count === $item2Count) {
                if ($item1Timestamp === $item2Timestamp) {
                    return 0;
                }

                return ($item1Timestamp > $item2Timestamp) ? -1 : 1;
            }

            return ($item1Count > $item2Count) ? -1 : 1;
        });

        foreach ($allEpisodes as &$item) {
            $item = $item[0];
        }

        if ($leastWatched) {
            $allEpisodes = array_reverse($allEpisodes);
        }

        return array_slice($allEpisodes, 0, $count);
    }

    public function getUnwatchedEpisodes(User $user): array
    {
        $episodes = [];

        foreach ($this->getSeasons() as $season) {
            foreach ($season->getEpisodes() as $episode) {
                $views = $episode->getViewsForUser($user);
                if (count($views)) {
                    continue;
                }

                $episodes[] = $episode;
            }
        }

        return $episodes;
    }
}