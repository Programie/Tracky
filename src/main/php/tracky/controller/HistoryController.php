<?php
namespace tracky\controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use tracky\datetime\Date;
use tracky\datetime\DateRange;
use tracky\HistoryEntry;
use tracky\model\User;
use tracky\orm\EpisodeRepository;
use tracky\orm\MovieRepository;
use tracky\orm\ViewRepository;
use tracky\Pagination;
use tracky\ViewType;
use tracky\watchstats\WatchStatsProvider;

class HistoryController extends AbstractController
{
    public function __construct(
        private readonly int $itemsPerPage,
        private readonly int $maxPreviousNextPages
    )
    {
    }

    #[Route("/users/{username}/history", name: "user_profile_history_page")]
    public function getPage(Request $request, User $user, ViewRepository $viewRepository, EpisodeRepository $episodeRepository, MovieRepository $movieRepository)
    {
        $page = $request->query->getInt("page", 1);

        if ($page <= 0) {
            $page = 1;
        }

        $type = ViewType::tryFrom(strtolower($request->query->get("type")));
        $item = $request->query->getInt("item");

        if (!$item) {
            $item = null;
        }

        $dateRange = DateRange::fromString(trim($request->query->getString("startdate")), trim($request->query->getString("enddate", (new Date())->format("c"))), Date::class);

        $criteria = ["user" => $user->getId()];

        switch ($type) {
            case ViewType::EPISODE:
                $criteria["item"] = $item;
                break;
            case ViewType::MOVIE:
                $criteria["item"] = $item;
                break;
        }

        $count = $viewRepository->count($criteria, $type, $dateRange);

        $pagination = new Pagination($page, $count, $this->itemsPerPage, $this->maxPreviousNextPages);

        if ($dateRange === null) {
            $firstPage = $viewRepository->getPaged($criteria, 1, $this->itemsPerPage, $type, $dateRange);
            $lastPage = $viewRepository->getPaged($criteria, $pagination->getLastPage(), $this->itemsPerPage, $type, $dateRange);

            if (!empty($firstPage) and !empty($lastPage)) {
                $dateRange = new DateRange($lastPage[count($lastPage) - 1]->getDateTime()->toDate(), $firstPage[0]->getDateTime()->toDate());
            }
        }

        $views = $viewRepository->getPaged($criteria, $page, $this->itemsPerPage, $type, $dateRange);

        return $this->render("user/history.twig", [
            "user" => $user,
            "dateRange" => $dateRange,
            "filter" => [
                "type" => $type?->value,
                "item" => $item
            ],
            "pagination" => $pagination,
            "entries" => HistoryEntry::getFromViews($views, $episodeRepository, $movieRepository, new WatchStatsProvider($viewRepository, $user))
        ]);
    }
}
