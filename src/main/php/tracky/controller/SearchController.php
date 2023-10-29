<?php
namespace tracky\controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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

    #[Route("/search", name: "searchPage")]
    public function getShowsPage(Request $request): Response
    {
        $query = trim($request->query->get("query", ""));

        $results = [];

        if ($query !== "") {
            $results = array_merge($results, $this->showRepository->search($query));
            $results = array_merge($results, $this->episodeRepository->search($query));
            $results = array_merge($results, $this->movieRepository->search($query));
        }

        return $this->render("search.twig", [
            "query" => $query,
            "results" => $results
        ]);
    }
}