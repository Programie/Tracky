<?php
namespace tracky\controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use tracky\orm\EpisodeRepository;
use tracky\orm\MovieRepository;
use tracky\orm\ViewRepository;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly int $maxEpisodes,
        private readonly int $maxMovies
    )
    {
    }

    #[Route("/", name: "homePage")]
    public function home(EpisodeRepository $episodeRepository, MovieRepository $movieRepository, ViewRepository $viewRepository): Response
    {
        $latestWatchedEpisodes = null;
        $latestWatchedMovies = null;

        $user = $this->getUser();
        if ($user !== null) {
            $latestWatchedEpisodes = $viewRepository->findBy(["user" => $user->getId()], ["dateTime" => "desc"], $this->maxEpisodes, type: "episode");
            $latestWatchedMovies = $viewRepository->findBy(["user" => $user->getId()], ["dateTime" => "desc"], $this->maxMovies, type: "movie");
        }

        return $this->render("index.twig", [
            "latestEpisodes" => $episodeRepository->findBy([], ["firstAired" => "DESC"], $this->maxEpisodes),
            "latestMovies" => $movieRepository->findBy([], ["year" => "DESC"], $this->maxMovies),
            "latestWatchedEpisodes" => $latestWatchedEpisodes,
            "latestWatchedMovies" => $latestWatchedMovies
        ]);
    }
}