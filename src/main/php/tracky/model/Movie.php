<?php
namespace tracky\model;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use tracky\model\traits\Plot;
use tracky\model\traits\PosterImage;
use tracky\model\traits\DataProvider;
use tracky\orm\MovieRepository;

#[ORM\Entity(repositoryClass: MovieRepository::class)]
#[ORM\Table(name: "movies")]
class Movie extends BaseEntity
{
    use DataProvider;
    use Plot;
    use PosterImage;

    #[ORM\Column(type: "string")]
    private string $title;

    #[ORM\Column(type: "integer")]
    private ?int $year;

    #[ORM\OneToMany(mappedBy: "item", targetEntity: MovieView::class)]
    #[ORM\OrderBy(["dateTime" => "ASC"])]
    private mixed $views;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): Movie
    {
        $this->title = $title;
        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): Movie
    {
        $this->year = $year;
        return $this;
    }

    public function getViewsForUser(User $user)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq("user", $user));

        return $this->views->matching($criteria);
    }
}