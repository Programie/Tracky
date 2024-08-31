<?php
namespace tracky\controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use tracky\orm\EpisodeRepository;
use tracky\orm\MovieRepository;
use tracky\orm\ShowRepository;
use tracky\orm\ViewRepository;

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
    public function home(ShowRepository $showRepository, EpisodeRepository $episodeRepository, MovieRepository $movieRepository, ViewRepository $viewRepository): Response
    {
        $latestWatchedEpisodes = null;
        $latestWatchedMovies = null;
        $nextEpisodes = [];

        $user = $this->getUser();
        if ($user !== null) {
            $latestWatchedEpisodes = $viewRepository->findBy(["user" => $user->getId()], ["dateTime" => "desc"], $this->maxEpisodes, type: "episode");
            $latestWatchedMovies = $viewRepository->findBy(["user" => $user->getId()], ["dateTime" => "desc"], $this->maxMovies, type: "movie");

            if ($this->maxNextEpisodeShows > 0) {
                foreach ($showRepository->findAllWithEpisodesAndViews($user->getId()) as $show) {
                    if (count($nextEpisodes) >= $this->maxNextEpisodeShows) {
                        break;
                    }

                    $episode = $show->getNextEpisodeToWatch($user, false);
                    if ($episode !== null) {
                        $nextEpisodes[] = $episode;
                    }
                }
            }
        }

        return $this->render("index.twig", [
            "latestEpisodes" => $episodeRepository->findBy([], ["firstAired" => "DESC"], $this->maxEpisodes),
            "latestMovies" => $movieRepository->findBy([], ["year" => "DESC"], $this->maxMovies),
            "latestWatchedEpisodes" => $latestWatchedEpisodes,
            "latestWatchedMovies" => $latestWatchedMovies,
            "nextEpisodes" => $nextEpisodes
        ]);
    }
}