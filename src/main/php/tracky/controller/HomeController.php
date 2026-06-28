<?php
namespace tracky\controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use tracky\HistoryEntry;
use tracky\orm\EpisodeRepository;
use tracky\orm\MovieRepository;
use tracky\orm\ShowRepository;
use tracky\orm\ViewRepository;
use tracky\scrobbler\Scrobbler;
use tracky\ViewType;
use tracky\watchstats\WatchStatsProvider;

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
    public function home(ShowRepository $showRepository, EpisodeRepository $episodeRepository, MovieRepository $movieRepository, ViewRepository $viewRepository, WatchStatsProvider $watchStatsProvider, Scrobbler $scrobbler): Response
    {
        $nowWatching = null;
        $latestWatchedEpisodes = null;
        $latestWatchedMovies = null;
        $nextEpisodes = null;

        $user = $this->getUser();
        if ($user !== null) {
            $nowWatching = $scrobbler->getNowWatching($user);

            $episodeViews = $viewRepository->findBy(["user" => $user->getId()], ["dateTime" => "desc"], $this->maxEpisodes, type: ViewType::EPISODE);
            $movieViews = $viewRepository->findBy(["user" => $user->getId()], ["dateTime" => "desc"], $this->maxMovies, type: ViewType::MOVIE);

            $latestWatchedEpisodes = HistoryEntry::getFromViews($episodeViews, $episodeRepository, $movieRepository, $watchStatsProvider);
            $latestWatchedMovies = HistoryEntry::getFromViews($movieViews, $episodeRepository, $movieRepository, $watchStatsProvider);

            $nextEpisodes = $this->getNextEpisodes($showRepository, $watchStatsProvider);
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

    private function getNextEpisodes(ShowRepository $showRepository, WatchStatsProvider $watchStatsProvider)
    {
        /**
         * @var list<array{Episode, DateTime}>
         */
        $episodes = [];

        foreach ($showRepository->findAllWithEpisodes() as $show) {
            $latestWatchedEpisode = $show->getLatestWatchedEpisodes($watchStatsProvider, 1)[0] ?? null;

            if ($latestWatchedEpisode === null) {
                continue;
            }

            $nextEpisode = $latestWatchedEpisode[0]->getNextEpisode();
            if ($nextEpisode === null) {
                continue;
            }

            $episodes[] = [$nextEpisode, $latestWatchedEpisode[1]->getLastWatched()];
        }

        usort($episodes, fn($item1, $item2) => $item2[1] <=> $item1[1]);

        return array_map(fn($item) => $item[0], array_slice($episodes, 0, $this->maxNextEpisodeShows));
    }
}
