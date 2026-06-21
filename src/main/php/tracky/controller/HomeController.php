<?php
namespace tracky\controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use tracky\model\Episode;
use tracky\model\User;
use tracky\orm\EpisodeRepository;
use tracky\orm\MovieRepository;
use tracky\orm\ShowRepository;
use tracky\orm\ViewRepository;
use tracky\scrobbler\Scrobbler;
use tracky\ViewType;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly int $maxEpisodes,
        private readonly int $maxMovies,
        private readonly int $maxNextEpisodeShows
    )
    {
    }

    #[Route("/", name: "home_page")]
    public function home(ShowRepository $showRepository, EpisodeRepository $episodeRepository, MovieRepository $movieRepository, ViewRepository $viewRepository, Scrobbler $scrobbler): Response
    {
        $nowWatching = null;
        $latestWatchedEpisodes = null;
        $latestWatchedMovies = null;
        $nextEpisodes = null;

        $user = $this->getUser();
        if ($user !== null) {
            $nowWatching = $scrobbler->getNowWatching($user);
            $latestWatchedEpisodes = $viewRepository->findBy(["user" => $user->getId()], ["dateTime" => "desc"], $this->maxEpisodes, type: ViewType::EPISODE);
            $latestWatchedMovies = $viewRepository->findBy(["user" => $user->getId()], ["dateTime" => "desc"], $this->maxMovies, type: ViewType::MOVIE);
            $nextEpisodes = $this->getNextEpisodes($showRepository, $viewRepository, $user);
        }

        return $this->render("index.twig", [
            "nowWatching" => $nowWatching,
            "latestEpisodes" => $episodeRepository->findBy([], ["firstAired" => "DESC"], $this->maxEpisodes),
            "latestMovies" => $movieRepository->findBy([], ["year" => "DESC"], $this->maxMovies),
            "latestWatchedEpisodes" => $latestWatchedEpisodes,
            "latestWatchedMovies" => $latestWatchedMovies,
            "nextEpisodes" => $nextEpisodes
        ]);
    }

    private function getNextEpisodes(ShowRepository $showRepository, ViewRepository $viewRepository, User $user)
    {
        $latestEpisodes = [];
        $nextEpisodes = [];

        $watchStats = $viewRepository->getEpisodeWatchStatsForUser($user);

        $latestEpisodes = [];

        foreach ($showRepository->findAllWithEpisodes() as $show) {
            $mostRecentWatch = null;
            $mostRecentWatchedEpisode = null;

            foreach ($show->getSeasons() as $season) {
                foreach ($season->getEpisodes() as $episode) {
                    $episodeWatchStats = $watchStats[$episode->getId()] ?? null;
                    if ($episodeWatchStats === null) {
                        continue;
                    }

                    if ($mostRecentWatch === null or $episodeWatchStats["lastWatched"] > $mostRecentWatch) {
                        $mostRecentWatch = $episodeWatchStats["lastWatched"];
                        $mostRecentWatchedEpisode = $episode;
                    }
                }
            }

            if ($mostRecentWatch === null) {
                continue;
            }

            $latestEpisodes[] = [
                "episode" => $mostRecentWatchedEpisode,
                "lastWatched" => $mostRecentWatch
            ];
        }

        usort($latestEpisodes, function ($item1, $item2) {
            $item1Timestamp = $item1["lastWatched"];
            $item2Timestamp = $item2["lastWatched"];

            if ($item1Timestamp === $item2Timestamp) {
                return 0;
            }

            return ($item1Timestamp > $item2Timestamp) ? -1 : 1;
        });

        foreach ($latestEpisodes as $item) {
            $episode = $item["episode"];
            $nextEpisode = $episode->getNextEpisode();
            if ($nextEpisode !== null) {
                $nextEpisodes[] = $nextEpisode;
            }
        }

        return array_slice($nextEpisodes, 0, $this->maxNextEpisodeShows);
    }
}
