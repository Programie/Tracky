<?php
namespace tracky\controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use tracky\model\EpisodeView;
use tracky\model\MovieView;
use tracky\model\User;
use tracky\model\ViewEntry;
use tracky\orm\ViewRepository;
use tracky\Pagination;

class HistoryController extends AbstractController
{
    #[Route("/users/{username}/history", name: "userHistoryPage")]
    public function getPage(Request $request, User $user, EntityManagerInterface $entityManager, ViewRepository $viewRepository)
    {
        $itemsPerPage = 100;
        $page = $request->query->getInt("page", 1);

        $type = strtolower($request->query->get("type"));
        $item = $request->query->getInt("item");

        $criteria = ["user" => $user->getId()];

        switch ($type) {
            case "episode":
                $criteria["item"] = $item;
                $viewRepository = $entityManager->getRepository(EpisodeView::class);
                break;
            case "movie":
                $criteria["item"] = $item;
                $viewRepository = $entityManager->getRepository(MovieView::class);
                break;
            default:
                $type = null;
                break;
        }

        $count = $viewRepository->count($criteria, $type);
        $entries = $viewRepository->getPaged($criteria, $page, $itemsPerPage, $type);

        usort($entries, function (ViewEntry $entry1, ViewEntry $entry2) {
            if ($entry1->getDateTime() < $entry2->getDateTime()) {
                return 1;
            } elseif ($entry1->getDateTime() > $entry2->getDateTime()) {
                return -1;
            } else {
                return 0;
            }
        });

        return $this->render("user/history/page.twig", [
            "user" => $user,
            "pagination" => new Pagination($page, $count, $itemsPerPage, 3),
            "entries" => $entries
        ]);
    }
}