<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;
use tracky\datetime\Date;
use tracky\datetime\DateRange;
use tracky\ImageFetcher;
use tracky\model\traits\Plot;
use tracky\model\traits\PosterImage;
use tracky\orm\SeasonRepository;
use tracky\watchstats\WatchStatsProvider;

#[ORM\Entity(repositoryClass: SeasonRepository::class)]
#[ORM\Table(
    name: "seasons",
    uniqueConstraints: [
        new ORM\UniqueConstraint(
            name: "uniq_seasons_show_number",
            columns: ["show", "number"]
        )
    ]
)]
class Season extends BaseEntity
{
    use PosterImage;
    use Plot;

    #[ORM\ManyToOne(targetEntity: Show::class)]
    #[ORM\JoinColumn(name: "`show`", referencedColumnName: "id")]
    private Show $show;

    #[ORM\Column(type: "integer")]
    private int $number;

    #[ORM\OneToMany(mappedBy: "season", targetEntity: Episode::class, cascade: ["persist"])]
    #[ORM\OrderBy(["number" => "ASC"])]
    private mixed $episodes = [];

    public function getShow(): Show
    {
        return $this->show;
    }

    public function setShow(Show $show): Season
    {
        $this->show = $show;
        return $this;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): Season
    {
        $this->number = $number;
        return $this;
    }

    /**
     * @return Episode[]
     */
    public function getEpisodes(): mixed
    {
        return $this->episodes;
    }

    /**
     * @param Episode[] $episodes
     * @return $this
     */
    public function setEpisodes(mixed $episodes): Season
    {
        $this->episodes = $episodes;
        return $this;
    }

    public function addEpisode(Episode $episode): Season
    {
        $this->episodes[] = $episode;
        return $this;
    }

    public function getEpisode(int $episode): ?Episode
    {
        foreach ($this->getEpisodes() as $episodeEntry) {
            if ($episodeEntry->getNumber() === $episode) {
                return $episodeEntry;
            }
        }

        return null;
    }

    public function getOrCreateEpisode(int $episodeNumber, bool &$created = false): Episode
    {
        $episode = $this->getEpisode($episodeNumber);
        if ($episode === null) {
            $episode = new Episode;
            $episode->setSeason($this);
            $episode->setNumber($episodeNumber);

            $this->addEpisode($episode);

            $created = true;
        }

        return $episode;
    }

    public function getRelativeSeason(int $addNumber): ?Season
    {
        $show = $this->getShow();

        return $show->getSeason($this->getNumber() + $addNumber);
    }

    public function getPreviousSeason(): ?Season
    {
        return $this->getRelativeSeason(-1);
    }

    public function getNextSeason(): ?Season
    {
        return $this->getRelativeSeason(1);
    }

    public function fetchPosterImages(ImageFetcher $imageFetcher, bool $includeEpisodes): void
    {
        $this->fetchPosterImage($imageFetcher);

        if ($includeEpisodes) {
            foreach ($this->getEpisodes() as $episode) {
                $episode->fetchPosterImage($imageFetcher);
            }
        }
    }

    public function getTotalRuntime(): int
    {
        $totalRuntime = 0;

        foreach ($this->getEpisodes() as $episode) {
            $runtime = $episode->getRuntime();
            if ($runtime === null) {
                continue;
            }

            $totalRuntime += $runtime;
        }

        return $totalRuntime;
    }

    public function getFirstAired(): ?Date
    {
        foreach ($this->getEpisodes() as $episode) {
            $airDate = $episode->getFirstAired();
            if ($airDate === null) {
                continue;
            }

            return $airDate;
        }

        return null;
    }

    public function getFirstAiredRange(): ?DateRange
    {
        $dates = [];

        foreach ($this->getEpisodes() as $episode) {
            $airDate = $episode->getFirstAired();
            if ($airDate === null) {
                continue;
            }

            $dates[] = $airDate;
        }

        if (empty($dates)) {
            return null;
        }

        return new DateRange(min($dates), max($dates));
    }

    public function getYear(): ?int
    {
        $airDate = $this->getFirstAired();
        if ($airDate === null) {
            return null;
        }

        return (int)$airDate->format("Y");
    }

    public function getWatchProgress(WatchStatsProvider $watchStatsProvider): int
    {
        $totalEpisodes = count($this->getEpisodes());
        $watchedEpisode = 0;

        if (!$totalEpisodes) {
            return 0;
        }

        foreach ($this->getEpisodes() as $episode) {
            if ($episode->isMarkedAsWatched($watchStatsProvider)) {
                $watchedEpisode++;
            }
        }

        return (int)round(($watchedEpisode / $totalEpisodes) * 100);
    }
}
