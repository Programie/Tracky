<?php
namespace tracky\controller;

use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use tracky\datetime\DateTime;
use tracky\ImageFetcher;
use tracky\model\Movie;
use tracky\model\MovieView;
use tracky\orm\MovieRepository;
use tracky\orm\ViewRepository;

class MovieController extends AbstractController
{
    public function __construct(
        private readonly MovieRepository $movieRepository,
        private readonly ViewRepository  $viewRepository
    )
    {
    }

    #[Route("/movies", name: "moviesPage")]
    public function getMoviesPage(): Response
    {
        return $this->render("movies.twig", [
            "movies" => $this->movieRepository->findBy([], ["title" => "asc"])
        ]);
    }

    #[Route("/movies/{movie}.jpg", name: "getMovieImage")]
    public function getMovieImage(Movie $movie, ImageFetcher $imageFetcher): Response
    {
        $url = $movie->getPosterImageUrl();
        if ($url === null) {
            throw new NotFoundHttpException("Image not available");
        }

        $path = $imageFetcher->get($url);
        if ($path === null) {
            throw new RuntimeException("Unable to fetch image");
        }

        return $this->file($path, null, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    #[Route("/movies/{movie}", name: "moviePage", methods: ["GET"])]
    public function getMoviePage(Movie $movie): Response
    {
        return $this->render("movie.twig", [
            "movie" => $movie
        ]);
    }

    #[Route("/movies/{movie}", name: "removeMovie", methods: ["DELETE"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function removeMovie(Movie $movie, EntityManagerInterface $entityManager): Response
    {
        $viewRepository = $entityManager->getRepository(MovieView::class);

        // Make sure no view exists for this movie
        if ($viewRepository->count(["item" => $movie->getId()], type: "movie")) {
            return $this->json([
                "error" => "view-exists"
            ], 409);
        }

        $entityManager->remove($movie);
        $entityManager->flush();

        return new Response("Movie removed from database");
    }

    #[Route("/movies/{movie}/views", name: "addMovieView", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function addView(Movie $movie, EntityManagerInterface $entityManager): Response
    {
        $movieView = new MovieView;
        $movieView->setMovie($movie);
        $movieView->setUser($this->getUser());
        $movieView->setDateTime(new DateTime);

        $entityManager->persist($movieView);
        $entityManager->flush();

        return new Response("View added to database");
    }

    #[Route("/movies/{movie}/views/all", name: "removeMovieViews", methods: ["DELETE"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function removeViewsByEpisode(Movie $movie, EntityManagerInterface $entityManager): Response
    {
        $views = $movie->getViewsForUser($this->getUser());

        foreach ($views as $view) {
            $entityManager->remove($view);
        }

        $entityManager->flush();

        return new Response("Views removed from database");
    }

    #[Route("/movies/{movie}/views/{entryId}", name: "removeMovieViewById", methods: ["DELETE"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function removeViewById(Movie $movie, int $entryId, EntityManagerInterface $entityManager): Response
    {
        $view = $this->viewRepository->findOneBy(["id" => $entryId, "user" => $this->getUser()], type: "movie");
        if ($view === null) {
            throw new NotFoundHttpException("View not found");
        }

        $entityManager->remove($view);
        $entityManager->flush();

        return new Response("View removed from database");
    }
}