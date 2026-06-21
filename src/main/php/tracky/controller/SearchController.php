<?php
namespace tracky\controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use tracky\orm\EpisodeRepository;
use tracky\orm\MovieRepository;
use tracky\orm\ShowRepository;

class SearchController extends AbstractController
{
    public function __construct(
        private readonly ShowRepository    $showRepository,
        private readonly EpisodeRepository $episodeRepository,
        private readonly MovieRepository   $movieRepository
    )
    {
    }

    #[Route("/search", name: "search_page")]
    public function getSearchPage(Request $request): Response
    {
        $query = trim($request->query->get("query", ""));

        $results = null;

        if ($query !== "") {
            $results = [
                "shows" => $this->showRepository->search($query, false),
                "episodes" => $this->episodeRepository->search($query),
                "movies" => $this->movieRepository->search($query)
            ];

            if (!array_any($results, fn($value) => !empty($value))) {
                $results = [];
            }
        }

        return $this->render("search.twig", [
            "query" => $query,
            "results" => $results
        ]);
    }
}
