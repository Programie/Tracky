<?php
namespace tracky\controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use tracky\HistoryEntry;
use tracky\model\User;
use tracky\orm\EpisodeRepository;
use tracky\orm\MovieRepository;
use tracky\orm\ShowRepository;
use tracky\orm\ViewRepository;
use tracky\scrobbler\Scrobbler;
use tracky\settings\SettingName;
use tracky\settings\UserSettings;
use tracky\ViewType;
use tracky\watchstats\WatchStatsProvider;

class HomeController extends AbstractController
{
    #[Route("/", name: "home_page")]
    public function home(
        ShowRepository $showRepository,
        EpisodeRepository $episodeRepository,
        MovieRepository $movieRepository,
        ViewRepository $viewRepository,
        WatchStatsProvider $watchStatsProvider,
        Scrobbler $scrobbler
    ): Response
    {
        $nowWatching = null;
        $latestWatchedEpisodes = null;
        $latestWatchedMovies = null;
        $nextEpisodes = null;

        /**
         * @var User
         */
        $user = $this->getUser();

        $userSettings = $user?->getSettings() ?? new UserSettings;

        $maxEpisodes = $userSettings->getOptionValue(SettingName::OVERVIEW_MAX_EPISODES);
        $maxMovies = $userSettings->getOptionValue(SettingName::OVERVIEW_MAX_MOVIES);
        $maxNextEpisodeShows = $userSettings->getOptionValue(SettingName::OVERVIEW_MAX_NEXT_EPISODE_SHOWS);

        if ($user !== null) {
            $nowWatching = $scrobbler->getNowWatching($user);

            $episodeViews = $viewRepository->findBy(["user" => $user->getId()], ["dateTime" => "desc"], $maxEpisodes, type: ViewType::EPISODE);
            $movieViews = $viewRepository->findBy(["user" => $user->getId()], ["dateTime" => "desc"], $maxMovies, type: ViewType::MOVIE);

            $latestWatchedEpisodes = HistoryEntry::getFromViews($episodeViews, $episodeRepository, $movieRepository, $watchStatsProvider);
            $latestWatchedMovies = HistoryEntry::getFromViews($movieViews, $episodeRepository, $movieRepository, $watchStatsProvider);

            $nextEpisodes = $this->getNextEpisodes($showRepository, $watchStatsProvider, $maxNextEpisodeShows);
        }

        return $this->render("index.twig", [
            "nowWatching" => $nowWatching,
            "latestEpisodes" => $episodeRepository->findBy([], ["firstAired" => "DESC"], $maxEpisodes),
            "latestMovies" => $movieRepository->findBy([], ["year" => "DESC"], $maxMovies),
            "latestWatchedEpisodes" => $latestWatchedEpisodes,
            "latestWatchedMovies" => $latestWatchedMovies,
            "nextEpisodes" => $nextEpisodes
        ]);
    }

    private function getNextEpisodes(ShowRepository $showRepository, WatchStatsProvider $watchStatsProvider, int $maxNextEpisodeShows)
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

        return array_map(fn($item) => $item[0], array_slice($episodes, 0, $maxNextEpisodeShows));
    }
}
