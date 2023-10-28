<?php
namespace tracky\controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use tracky\model\User;
use tracky\model\ViewEntry;
use tracky\orm\EpisodeViewRepository;
use tracky\orm\MovieViewRepository;

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
        $page = $request->query->getInt("page", 1);

        $episodes = $this->episodeViewRepository->getPaged($user, $page, 100);
        $movies = $this->movieViewRepository->getPaged($user, $page, 100);

        $entries = array_merge($episodes, $movies);

        usort($episodes, function (ViewEntry $entry1, ViewEntry $entry2) {
            if ($entry1->getDateTime() > $entry2->getDateTime()) {
                return 1;
            } elseif ($entry1->getDateTime() < $entry2->getDateTime()) {
                return -1;
            } else {
                return 0;
            }
        });

        return $this->render("history.twig", [
            "entries" => $entries
        ]);
    }
}