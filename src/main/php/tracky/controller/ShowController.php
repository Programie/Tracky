<?php
namespace tracky\controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use tracky\model\Show;
use tracky\orm\ShowRepository;

class ShowController extends AbstractController
{
    public function __construct(
        private readonly ShowRepository $showRepository
    )
    {
    }

    #[Route("/shows", name: "showsPage")]
    public function getShowsPage(): Response
    {
        return $this->render("shows.twig", [
            "shows" => $this->showRepository->findAll()
        ]);
    }

    #[Route("/shows/{id}", name: "showOverviewPage")]
    public function getShowOverviewPage(Show $show): Response
    {
        return $this->render("show.twig", [
            "show" => $show
        ]);
    }

    #[Route("/shows/{id}/seasons/{number}", name: "seasonPage")]
    public function getSeasonPage(Show $show, int $number): Response
    {
        $season = $show->getSeason($number);
        if ($season === null) {
            throw new NotFoundHttpException("Season not found!");
        }

        $seasons = $show->getSeasons();

        $seasonNumbers = [];

        foreach ($seasons as $thisSeason) {
            $seasonNumbers[] = $thisSeason->getNumber();
        }

        $previousSeasonIndex = array_search($number - 1, $seasonNumbers, true);
        if ($previousSeasonIndex !== false) {
            $previousSeason = $seasonNumbers[$previousSeasonIndex];
        } else {
            $previousSeason = null;
        }

        $nextSeasonIndex = array_search($number + 1, $seasonNumbers, true);
        if ($nextSeasonIndex !== false) {
            $nextSeason = $seasonNumbers[$nextSeasonIndex];
        } else {
            $nextSeason = null;
        }

        return $this->render("season.twig", [
            "show" => $show,
            "season" => $season,
            "pagination" => [
                "previousSeason" => $previousSeason,
                "nextSeason" => $nextSeason
            ]
        ]);
    }
}