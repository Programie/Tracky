<?php
namespace tracky\controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use tracky\model\User;
use tracky\model\ViewEntry;
use tracky\orm\ViewRepository;
use tracky\Pagination;

class HistoryController extends AbstractController
{
    public function __construct(private readonly ViewRepository $viewRepository)
    {
    }

    #[Route("/users/{username}/history", name: "userHistoryPage")]
    public function getPage(Request $request, User $user)
    {
        $itemsPerPage = 100;
        $page = $request->query->getInt("page", 1);

        $criteria = ["user" => $user->getId()];

        $episode = $request->query->getInt("episode");
        $movie = $request->query->getInt("movie");

        if ($episode) {
            $type = "episode";
            $criteria["item"] = $episode;
        } elseif ($movie) {
            $type = "movie";
            $criteria["item"] = $movie;
        } else {
            $type = null;
        }

        $count = $this->viewRepository->count($criteria, $type);
        $entries = $this->viewRepository->getPaged($criteria, $page, $itemsPerPage, $type);

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