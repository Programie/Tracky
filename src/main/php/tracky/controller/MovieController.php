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
use tracky\model\View;
use tracky\orm\MovieRepository;
use tracky\orm\ViewRepository;
use tracky\ViewType;

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

    #[Route("/movies", name: "movies_page")]
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

        if ($user !== null) {
            $watchStats = $this->viewRepository->getWatchStatsForUser($user, ViewType::MOVIE);
        } else {
            $watchStats = null;
        }

        // Special sorting for playCount and lastPlayed
        if ($user !== null and ($sortField === "playCount" or $sortField === "lastPlayed")) {
            $movies = $this->movieRepository->findAll();

            usort($movies, function (Movie $movie1, Movie $movie2) use ($sortField, $sortDirection, $watchStats) {
                $item1 = $watchStats->getStatsForItem($movie1);
                $item2 = $watchStats->getStatsForItem($movie2);

                $value1 = 0;
                $value2 = 0;

                switch ($sortField) {
                    case "playCount":
                        $value1 = $item1?->getCount() ?? 0;
                        $value2 = $item2?->getCount() ?? 0;
                        break;
                    case "lastPlayed":
                        $value1 = $item1?->getLastWatched()?->getTimestamp() ?? 0;
                        $value2 = $item2?->getLastWatched()?->getTimestamp() ?? 0;
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
            "movies" => $movies,
            "watchStats" => $watchStats
        ]);
    }

    #[Route("/movies/{movie}.jpg", name: "movies_get_image")]
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

    #[Route("/movies/{movie}", name: "movies_single_page", methods: ["GET"])]
    public function getMoviePage(Movie $movie): Response
    {
        $user = $this->getUser();
        if ($user !== null) {
            $itemWatchStats = $this->viewRepository->getWatchStatsForUser($user, ViewType::MOVIE)->getStatsForItem($movie);
        } else {
            $itemWatchStats = null;
        }

        return $this->render("movie.twig", [
            "movie" => $movie,
            "itemWatchStats" => $itemWatchStats
        ]);
    }

    #[Route("/movies/{movie}", name: "movies_remove_movie_action", methods: ["DELETE"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function removeMovie(Movie $movie, ViewRepository $viewRepository, EntityManagerInterface $entityManager): Response
    {
        // Make sure no view exists for this movie
        if ($viewRepository->count(["item" => $movie->getId()], type: ViewType::MOVIE)) {
            return $this->json([
                "error" => "view-exists"
            ], 409);
        }

        $entityManager->remove($movie);
        $entityManager->flush();

        return new Response("Movie removed from database");
    }

    #[Route("/movies/{movie}/views", name: "movies_add_view_action", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function addView(Movie $movie, Request $request, EntityManagerInterface $entityManager): Response
    {
        try {
            $dateTime = new DateTime($request->getPayload()->get("timestamp"));
        } catch (Exception) {
            throw new BadRequestException("Invalid payload");
        }

        $view = new View;
        $view->setItem($movie);
        $view->setUser($this->getUser());
        $view->setDateTime($dateTime);
        $view->setType(ViewType::MOVIE);

        $entityManager->persist($view);
        $entityManager->flush();

        return new Response("View added to database");
    }

    #[Route("/movies/{movie}/views/all", name: "movies_remove_views_action", methods: ["DELETE"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function removeViewsByMovie(Movie $movie, ViewRepository $viewRepository, EntityManagerInterface $entityManager): Response
    {
        $views = $viewRepository->findBy(["item" => $movie->getId(), "user" => $this->getUser(), "type" => ViewType::MOVIE->value]);

        foreach ($views as $view) {
            $entityManager->remove($view);
        }

        $entityManager->flush();

        return new Response("Views removed from database");
    }

    #[Route("/movies/{movie}/views/{entryId}", name: "movies_remove_view_by_id_action", methods: ["DELETE"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function removeViewById(Movie $movie, int $entryId, EntityManagerInterface $entityManager): Response
    {
        $view = $this->viewRepository->findOneBy(["id" => $entryId, "user" => $this->getUser()], type: ViewType::MOVIE);
        if ($view === null) {
            throw new NotFoundHttpException("View not found");
        }

        $entityManager->remove($view);
        $entityManager->flush();

        return new Response("View removed from database");
    }
}
