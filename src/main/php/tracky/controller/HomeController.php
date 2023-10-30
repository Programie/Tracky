<?php
namespace tracky\controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use tracky\orm\EpisodeRepository;
use tracky\orm\MovieRepository;

class HomeController extends AbstractController
{
    #[Route("/", name: "homePage")]
    public function home(EpisodeRepository $episodeRepository, MovieRepository $movieRepository): Response
    {
        return $this->render("index.twig", [
            "latestEpisodes" => $episodeRepository->findBy([], ["firstAired" => "DESC"], 10),
            "latestMovies" => $movieRepository->findBy([], ["year" => "DESC"], 10)
        ]);
    }
}