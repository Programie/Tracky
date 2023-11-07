<?php
namespace tracky\controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use tracky\model\User;
use tracky\model\ViewEntry;
use tracky\orm\EpisodeViewRepository;
use tracky\orm\MovieViewRepository;
use tracky\Pagination;

class HistoryController extends AbstractController
{
    public function __construct(
        private readonly EpisodeViewRepository $episodeViewRepository,
        private readonly MovieViewRepository   $movieViewRepository
    )
    {
    }

    #[Route("/users/{username}/history", name: "userHistoryPage")]
    public function getPage(Request $request, User $user)
    {
        $itemsPerPage = 100;
        $page = $request->query->getInt("page", 1);

        $episode = $request->query->getInt("episode");
        $movie = $request->query->getInt("movie");

        if ($episode) {
            $count = $this->episodeViewRepository->count(["user" => $user, "episode" => $episode]);
            $entries = $this->episodeViewRepository->getPaged(["user" => $user, "episode" => $episode], $page, $itemsPerPage);
        } elseif ($movie) {
            $count = $this->movieViewRepository->count(["user" => $user, "movie" => $episode]);
            $entries = $this->movieViewRepository->getPaged(["user" => $user, "movie" => $episode], $page, $itemsPerPage);
        } else {
            $episodesCount = $this->episodeViewRepository->count(["user" => $user]);
            $moviesCount = $this->episodeViewRepository->count(["user" => $user]);
            $count = max($episodesCount, $moviesCount);

            $episodes = $this->episodeViewRepository->getPaged(["user" => $user], $page, $itemsPerPage);
            $movies = $this->movieViewRepository->getPaged(["user" => $user], $page, $itemsPerPage);
            $entries = array_merge($episodes, $movies);
        }

        usort($entries, function (ViewEntry $entry1, ViewEntry $entry2) {
            if ($entry1->getDateTime() < $entry2->getDateTime()) {
                return 1;
            } elseif ($entry1->getDateTime() > $entry2->getDateTime()) {
                return -1;
            } else {
                return 0;
            }
        });

        return $this->render("history/page.twig", [
            "user" => $user,
            "pagination" => new Pagination($page, $count, $itemsPerPage, 3),
            "entries" => $entries
        ]);
    }
}