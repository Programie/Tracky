<?php
namespace tracky\controller;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use tracky\datetime\DateTime;
use tracky\model\Movie;
use tracky\model\MovieView;
use tracky\orm\EpisodeRepository;
use tracky\orm\EpisodeViewRepository;
use tracky\orm\MovieRepository;
use tracky\orm\MovieViewRepository;
use tracky\orm\ShowRepository;

class ScrobbleController extends AbstractController
{
    public function __construct(
        private readonly ShowRepository         $showRepository,
        private readonly EpisodeRepository      $episodeRepository,
        private readonly EpisodeViewRepository  $episodeViewRepository,
        private readonly MovieRepository        $movieRepository,
        private readonly MovieViewRepository    $movieViewRepository,
        private readonly EntityManagerInterface $entityManager
    )
    {
    }

    /**
     * @throws Exception
     */
    #[Route("/scrobble", name: "scrobble", methods: ["POST"])]
    public function scrobble(Request $request): string
    {
        $json = $request->toArray();

        $event = $json["event"] ?? "end";

        if ($event !== "end") {
            return sprintf("Event is '%s', only accepting 'end'", $event);
        }

        if (isset($json["timestamp"])) {
            $timestamp = new DateTime($json["timestamp"]);
        } else {
            $timestamp = new DateTime;
        }

        switch (strtolower($json["mediaType"])) {
            case "episode":
                $this->scrobbleEpisode($json, $timestamp);
                return "Episode view added to database";
            case "movie":
                $this->scrobbleMovie($json, $timestamp);
                return "Movie view added to database";
            default:
                throw new BadRequestException(sprintf("Invalid media type: %s", $json["mediaType"]));
        }
    }

    private function scrobbleEpisode(array $json, DateTime $dateTime): void
    {
    }

    private function scrobbleMovie(array $json, DateTime $dateTime): void
    {
        $title = trim($json["title"] ?? "");
        if ($title === "") {
            throw new BadRequestException("Title not specified!");
        }

        $movie = $this->movieRepository->getByTitle($title);
        if ($movie === null) {
            $movie = new Movie;
            $movie->setTitle($title);
            $movie->setYear($json["year"] ?? null);
            $this->entityManager->persist($movie);
        }

        $movieView = new MovieView;
        $movieView->setMovie($movie);
        $movieView->setDateTime($dateTime);
        $this->entityManager->persist($movieView);

        $this->entityManager->flush();
    }
}