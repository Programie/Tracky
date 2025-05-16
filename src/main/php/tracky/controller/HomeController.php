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

class HomeController extends AbstractController
{
    public function __construct(
        private readonly int $maxEpisodes,
        private readonly int $maxMovies,
        private readonly int $maxNextEpisodeShows
    )
    {
    }

    #[Route("/", name: "homePage")]
    public function home(ShowRepository $showRepository, EpisodeRepository $episodeRepository, MovieRepository $movieRepository, ViewRepository $viewRepository, Scrobbler $scrobbler): Response
    {
        $nowWatching = null;
        $latestWatchedEpisodes = null;
        $latestWatchedMovies = null;
        $nextEpisodes = null;

        $user = $this->getUser();
        if ($user !== null) {
            $nowWatching = $scrobbler->getNowWatching($user);
            $latestWatchedEpisodes = $viewRepository->findBy(["user" => $user->getId()], ["dateTime" => "desc"], $this->maxEpisodes, type: "episode");
            $latestWatchedMovies = $viewRepository->findBy(["user" => $user->getId()], ["dateTime" => "desc"], $this->maxMovies, type: "movie");
            $nextEpisodes = $this->getNextEpisodes($showRepository, $user);
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

    private function getNextEpisodes(ShowRepository $showRepository, User $user)
    {
        $latestEpisodes = [];
        $nextEpisodes = [];

        foreach ($showRepository->findAllWithEpisodesAndViews($user->getId()) as $show) {
            $latestWatchedEpisodes = $show->getLatestWatchedEpisodes($user, 1, true);
            if (!empty($latestWatchedEpisodes)) {
                $latestEpisodes[] = $latestWatchedEpisodes[0];
            }
        }

        usort($latestEpisodes, function ($item1, $item2) {
            list(, $item1Timestamp) = $item1;
            list(, $item2Timestamp) = $item2;


            if ($item1Timestamp === $item2Timestamp) {
                return 0;
            }

            return ($item1Timestamp > $item2Timestamp) ? -1 : 1;
        });

        foreach ($latestEpisodes as $item) {
            /**
             * @var Episode $episode
             */
            $episode = $item[0];
            $nextEpisode = $episode->getNextEpisode();
            if ($nextEpisode !== null) {
                $nextEpisodes[] = $nextEpisode;
            }
        }

        return array_slice($nextEpisodes, 0, $this->maxNextEpisodeShows);
    }
}
