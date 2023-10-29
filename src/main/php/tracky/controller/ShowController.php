<?php
namespace tracky\controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use tracky\model\Season;
use tracky\model\Show;
use tracky\orm\ShowRepository;

class ShowController extends AbstractController
{
    public function __construct(
        private readonly ShowRepository $showRepository
    )
    {
    }

    #[Route("/shows/{id}", name: "showOverviewPage")]
    public function getShowOverviewPage(Request $request, Show $show): Response
    {
        return $this->render("show.twig", [
            "show" => $show
        ]);
    }

    #[Route("/shows/{id}/seasons/{number}", name: "seasonPage")]
    public function getSeasonPage(Request $request, Show $show, int $number): Response
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