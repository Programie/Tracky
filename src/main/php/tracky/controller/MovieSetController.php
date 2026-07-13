<?php
namespace tracky\controller;

use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use tracky\ImageFetcher;
use tracky\model\MovieSet;
use tracky\orm\MovieSetRepository;
use tracky\orm\ViewRepository;
use tracky\ViewType;

class MovieSetController extends AbstractController
{
    const SORT_ASC = "asc";
    const SORT_DESC = "desc";

    public function __construct(
        private readonly MovieSetRepository $movieSetRepository,
        private readonly ViewRepository     $viewRepository
    )
    {
    }

    #[Route("/moviesets", name: "moviesets_page")]
    public function getMovieSetsPage(Request $request): Response
    {
        $sort = explode("/", trim($request->query->get("sort", "")), 2);

        list($sortField, $sortDirection) = $sort + ["", ""];

        $sortOptions = ["title", "year", "runtime"];

        if (!in_array($sortField, $sortOptions)) {
            $sortField = "title";
        }

        if (!in_array($sortDirection, [self::SORT_ASC, self::SORT_DESC])) {
            $sortDirection = self::SORT_ASC;
        }

        $movieSets = $this->movieSetRepository->findAllWithMovies();

        usort($movieSets, function (MovieSet $movieSet1, MovieSet $movieSet2) use ($sortField, $sortDirection) {
            $value1 = 0;
            $value2 = 0;

            switch ($sortField) {
                case "title":
                    $value1 = $movieSet1->getTitle();
                    $value2 = $movieSet2->getTitle();
                    break;
                case "year":
                    $value1 = $movieSet1->getMovies()[0]->getYear();
                    $value2 = $movieSet2->getMovies()[0]->getYear();
                    break;
                case "runtime":
                    $value1 = $movieSet1->getRuntime();
                    $value2 = $movieSet2->getRuntime();
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

        return $this->render("moviesets.twig", [
            "sortOptions" => $sortOptions,
            "sort" => [
                "field" => $sortField,
                "direction" => $sortDirection
            ],
            "movieSets" => $movieSets
        ]);
    }

    #[Route("/moviesets/{movieSet}.jpg", name: "moviesets_get_image")]
    public function getMovieSetImage(MovieSet $movieSet, ImageFetcher $imageFetcher): Response
    {
        $url = $movieSet->getPosterImageUrl();
        if ($url === null) {
            throw new NotFoundHttpException("Image not available");
        }

        $path = $imageFetcher->get($url);
        if ($path === null) {
            throw new RuntimeException("Unable to fetch image");
        }

        return $this->file($path, null, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    #[Route("/moviesets/{movieSet}", name: "moviesets_single_page", methods: ["GET"])]
    public function getMoviePage(MovieSet $movieSet): Response
    {
        $user = $this->getUser();

        if ($user !== null) {
            $watchStats = $this->viewRepository->getWatchStatsForUser($user, ViewType::MOVIE);
        } else {
            $watchStats = null;
        }

        return $this->render("movieset.twig", [
            "movieSet" => $movieSet,
            "watchStats" => $watchStats
        ]);
    }
}
