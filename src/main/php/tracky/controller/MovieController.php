<?php
namespace tracky\controller;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
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
    const SORT_ASC = "asc";
    const SORT_DESC = "desc";

    public function __construct(
        private readonly MovieRepository $movieRepository,
        private readonly ViewRepository  $viewRepository
    )
    {
    }

    #[Route("/movies", name: "moviesPage")]
    public function getMoviesPage(Request $request): Response
    {
        $user = $this->getUser();

        $sort = explode("/", trim($request->query->get("sort", "")), 2);

        list($sortField, $sortDirection) = $sort + ["", ""];

        $sortOptions = ["title", "year", "runtime"];

        if ($user !== null) {
            $sortOptions = array_merge($sortOptions, ["playCount", "lastPlayed"]);
        }

        if (!in_array($sortField, $sortOptions)) {
            $sortField = "title";
        }

        if (!in_array($sortDirection, [self::SORT_ASC, self::SORT_DESC])) {
            $sortDirection = self::SORT_ASC;
        }

        // Special sorting for playCount and lastPlayed
        if ($user !== null and ($sortField === "playCount" or $sortField === "lastPlayed")) {
            $movies = $this->movieRepository->findAllWithViews($user->getId());
            usort($movies, function (Movie $movie1, Movie $movie2) use ($user, $sortField, $sortDirection) {
                $views1 = $movie1->getViewsForUser($user);
                $views2 = $movie2->getViewsForUser($user);

                switch ($sortField) {
                    case "playCount":
                        $value1 = count($views1);
                        $value2 = count($views2);
                        break;
                    case "lastPlayed":
                        $lastView1 = $views1->last();
                        if ($lastView1 === false) {
                            $lastView1 = null;
                        }

                        $lastView2 = $views2->last();
                        if ($lastView2 === false) {
                            $lastView2 = null;
                        }

                        $value1 = $lastView1?->getDateTime()?->getTimestamp() ?? 0;
                        $value2 = $lastView2?->getDateTime()?->getTimestamp() ?? 0;
                        break;
                }

                if ($value1 < $value2) {
                    return $sortDirection == self::SORT_ASC ? -1 : 1;
                } elseif ($value1 > $value2) {
                    return $sortDirection == self::SORT_ASC ? 1 : -1;
                } else {
                    return 0;
                }
            });
        } else {
            $movies = $this->movieRepository->findBy([], [$sortField => $sortDirection]);
        }

        return $this->render("movies.twig", [
            "sortOptions" => $sortOptions,
            "sort" => [
                "field" => $sortField,
                "direction" => $sortDirection
            ],
            "movies" => $movies
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
    public function addView(Movie $movie, Request $request, EntityManagerInterface $entityManager): Response
    {
        try {
            $dateTime = new DateTime($request->getPayload()->get("timestamp"));
        } catch (Exception) {
            throw new BadRequestException("Invalid payload");
        }

        $movieView = new MovieView;
        $movieView->setMovie($movie);
        $movieView->setUser($this->getUser());
        $movieView->setDateTime($dateTime);

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
