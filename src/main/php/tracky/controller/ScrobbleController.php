<?php
namespace tracky\controller;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use tracky\dataprovider\TMDB;
use tracky\datetime\DateTime;
use tracky\model\EpisodeView;
use tracky\model\Movie;
use tracky\model\MovieView;
use tracky\model\Show;
use tracky\model\User;
use tracky\orm\MovieRepository;
use tracky\orm\ShowRepository;

class ScrobbleController extends AbstractController
{
    public function __construct(
        private readonly ShowRepository         $showRepository,
        private readonly MovieRepository        $movieRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly TMDB                   $tmdb
    )
    {
    }

    /**
     * @throws Exception
     */
    #[Route("/api/scrobble", name: "scrobble", methods: ["POST"])]
    public function scrobble(Request $request): Response
    {
        /**
         * @var $user User
         */
        $user = $this->getUser();
        if ($user === null) {
            throw new UnauthorizedHttpException("Tracky Scrobbler");
        }

        $json = $request->toArray();

        $event = $json["event"] ?? "end";

        if ($event !== "end") {
            return $this->returnPlainText(sprintf("Event is '%s', only accepting 'end'", $event));
        }

        if (isset($json["timestamp"])) {
            $timestamp = new DateTime($json["timestamp"]);
        } else {
            $timestamp = new DateTime;
        }

        if (!isset($json["mediaType"])) {
            throw new BadRequestException("Missing media type");
        }

        switch (strtolower($json["mediaType"])) {
            case "episode":
                $this->scrobbleEpisode($json, $timestamp, $user);
                return $this->returnPlainText("Episode view added to database");
            case "movie":
                $this->scrobbleMovie($json, $timestamp, $user);
                return $this->returnPlainText("Movie view added to database");
            default:
                throw new BadRequestException(sprintf("Invalid media type: %s", $json["mediaType"]));
        }
    }

    private function scrobbleEpisode(array $json, DateTime $dateTime, User $user): void
    {
        $seasonNumber = $json["season"] ?? null;
        $episodeNumber = $json["episode"] ?? null;

        if ($seasonNumber === null) {
            throw new BadRequestException("Missing season number");
        }

        if ($episodeNumber === null) {
            throw new BadRequestException("Missing episode number");
        }

        $tmdbId = $this->getTmdbId($json["uniqueIds"] ?? [], "tv");
        if ($tmdbId === null) {
            throw new BadRequestException("Unable to get TMDB ID");
        }

        $show = $this->showRepository->findOneBy(["tmdbId" => $tmdbId]);
        if ($show === null) {
            $show = new Show;
            $show->setTmdbId($tmdbId);
            $show->fetchTMDBData($this->tmdb);

            $this->entityManager->persist($show);
        }

        $season = $show->getSeason($seasonNumber);
        if ($season === null) {
            throw new BadRequestException("Unknown season");
        }

        $episode = $season->getEpisode($episodeNumber);
        if ($episode === null) {
            throw new BadRequestException("Unknown episode");
        }

        $episodeView = new EpisodeView;
        $episodeView->setEpisode($episode);
        $episodeView->setUser($user);
        $episodeView->setDateTime($dateTime);

        $this->entityManager->persist($episodeView);
        $this->entityManager->flush();
    }

    private function scrobbleMovie(array $json, DateTime $dateTime, User $user): void
    {
        $tmdbId = $this->getTmdbId($json["uniqueIds"] ?? [], "movie");
        if ($tmdbId === null) {
            throw new BadRequestException("Unable to get TMDB ID");
        }

        $movie = $this->movieRepository->findOneBy(["tmdbId" => $tmdbId]);
        if ($movie === null) {
            $movie = new Movie;
            $movie->setTmdbId($tmdbId);
            $movie->fetchTMDBData($this->tmdb);

            $this->entityManager->persist($movie);
        }

        $movieView = new MovieView;
        $movieView->setMovie($movie);
        $movieView->setUser($user);
        $movieView->setDateTime($dateTime);

        $this->entityManager->persist($movieView);
        $this->entityManager->flush();
    }

    private function getTmdbId(array $uniqueIds, string $expectedMediaType): ?int
    {
        $tmdbId = $uniqueIds["tmdb"] ?? null;
        if ($tmdbId !== null) {
            $tmdbId = (int)$tmdbId;
            if ($tmdbId === 0) {
                return null;
            }

            return $tmdbId;
        }

        $externalSources = [
            "imdb" => "imdb_id",
            "tvdb" => "tvdb_id"
        ];

        foreach ($externalSources as $provider => $externalSource) {
            $uniqueId = $uniqueIds[$provider] ?? null;
            if ($uniqueId === null) {
                continue;
            }

            $tmdbId = $this->tmdb->getTmdbIdFromExternalId($externalSource, $uniqueId, $expectedMediaType);
            if ($tmdbId !== null) {
                return $tmdbId;
            }
        }

        return null;
    }

    private function returnPlainText(string $content): Response
    {
        return new Response($content);
    }
}