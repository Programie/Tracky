<?php
namespace tracky\controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use tracky\HistoryEntry;
use tracky\orm\EpisodeRepository;
use tracky\orm\MovieRepository;
use tracky\orm\Settings;
use tracky\orm\ShowRepository;
use tracky\orm\ViewRepository;
use tracky\scrobbler\Scrobbler;
use tracky\ViewType;
use tracky\watchstats\WatchStatsProvider;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly SettingsController $settingsController
    )
    {
    }

    #[Route("/", name: "home_page")]
    public function home(
        ShowRepository $showRepository,
        EpisodeRepository $episodeRepository,
        MovieRepository $movieRepository,
        ViewRepository $viewRepository,
        WatchStatsProvider $watchStatsProvider,
        Scrobbler $scrobbler,
        EntityManagerInterface $entityManager
    ): Response
    {
        $nowWatching = null;
        $latestWatchedEpisodes = null;
        $latestWatchedMovies = null;
        $nextEpisodes = null;

        // Initialize fallback values from settings schema defaults
        $defaults = $this->settingsController->getSettingDefaults();
        $maxEpisodes = (int)($defaults['overviewMaxEpisodes']['default'] ?? 8);
        $maxMovies = (int)($defaults['overviewMaxMovies']['default'] ?? 8);
        $maxNextEpisodeShows = (int)($defaults['overviewMaxNextEpisodeShows']['default'] ?? 8);

        $user = $this->getUser();
        if ($user !== null) {
            $savedSettings = $entityManager->getRepository(Settings::class)->findBy(['user' => $user]);
            foreach ($savedSettings as $setting) {
                if ($setting->getSetting() === 'overviewMaxEpisodes') {
                    $maxEpisodes = (int)$setting->getValue();
                } elseif ($setting->getSetting() === 'overviewMaxMovies') {
                    $maxMovies = (int)$setting->getValue();
                } elseif ($setting->getSetting() === 'overviewMaxNextEpisodeShows') {
                    $maxNextEpisodeShows = (int)$setting->getValue();
                }
            }
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
