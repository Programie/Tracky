<?php
namespace tracky\controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use tracky\datetime\Date;
use tracky\datetime\DateRange;
use tracky\model\EpisodeView;
use tracky\model\MovieView;
use tracky\model\User;
use tracky\model\ViewEntry;
use tracky\orm\ViewRepository;
use tracky\Pagination;

class HistoryController extends AbstractController
{
    public function __construct(
        private readonly int $itemsPerPage,
        private readonly int $maxPreviousNextPages
    )
    {
    }

    #[Route("/users/{username}/history", name: "userHistoryPage")]
    public function getPage(Request $request, User $user, EntityManagerInterface $entityManager, ViewRepository $viewRepository)
    {
        $page = $request->query->getInt("page", 1);

        if ($page <= 0) {
            $page = 1;
        }

        $type = strtolower($request->query->get("type"));
        $item = $request->query->getInt("item");

        if (!$item) {
            $item = null;
        }

        $dateRange = DateRange::fromString(trim($request->query->getString("startdate")), trim($request->query->getString("enddate", (new Date())->format("c"))), Date::class);

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

        $count = $viewRepository->count($criteria, $type, $dateRange);

        $pagination = new Pagination($page, $count, $this->itemsPerPage, $this->maxPreviousNextPages);

        if ($dateRange === null) {
            $firstPage = $this->sorted($viewRepository->getPaged($criteria, 1, $this->itemsPerPage, $type, $dateRange));
            $lastPage = $this->sorted($viewRepository->getPaged($criteria, $pagination->getLastPage(), $this->itemsPerPage, $type, $dateRange));

            if (!empty($firstPage) and !empty($lastPage)) {
                $dateRange = new DateRange($lastPage[count($lastPage) - 1]->getDateTime()->toDate(), $firstPage[0]->getDateTime()->toDate());
            }
        }

        return $this->render("user/history.twig", [
            "user" => $user,
            "dateRange" => $dateRange,
            "filter" => [
                "type" => $type,
                "item" => $item
            ],
            "pagination" => $pagination,
            "entries" => $this->sorted($viewRepository->getPaged($criteria, $page, $this->itemsPerPage, $type, $dateRange))
        ]);
    }

    private function sorted(array $entries): array
    {
        usort($entries, function (ViewEntry $entry1, ViewEntry $entry2) {
            if ($entry1->getDateTime() < $entry2->getDateTime()) {
                return 1;
            } elseif ($entry1->getDateTime() > $entry2->getDateTime()) {
                return -1;
            } else {
                return 0;
            }
        });

        return $entries;
    }
}
