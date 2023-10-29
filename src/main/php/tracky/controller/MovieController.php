<?php
namespace tracky\controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use tracky\orm\MovieRepository;

class MovieController extends AbstractController
{
    public function __construct(
        private readonly MovieRepository $movieRepository
    )
    {
    }

    #[Route("/movies", name: "moviesPage")]
    public function getMoviesPage(): Response
    {
        return $this->render("movies.twig", [
            "movies" => $this->movieRepository->findAll()
        ]);
    }
}