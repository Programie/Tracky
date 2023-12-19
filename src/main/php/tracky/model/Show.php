<?php
namespace tracky\model;

use Doctrine\ORM\Mapping as ORM;
use tracky\datetime\Date;
use tracky\datetime\DateTime;
use tracky\model\traits\PosterImage;
use tracky\model\traits\DataProvider;
use tracky\orm\ShowRepository;

#[ORM\Entity(repositoryClass: ShowRepository::class)]
#[ORM\Table(name: "shows")]
class Show extends BaseEntity
{
    use DataProvider;
    use PosterImage;

    #[ORM\Column(type: "string")]
    private string $title;

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
        if ($this->getLastUpdate() === null) {
            return true;
        }

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

    public function getTotalEpisodes(): int
    {
        $totalEpisodes = 0;

        foreach ($this->getSeasons() as $season) {
            $totalEpisodes += count($season->getEpisodes());
        }

        return $totalEpisodes;
    }
}