<?php
namespace tracky\controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use tracky\datetime\DateTime;
use tracky\model\EpisodeView;
use tracky\model\Show;
use tracky\orm\EpisodeViewRepository;
use tracky\orm\ShowRepository;

class ShowController extends AbstractController
{
    public function __construct(
        private readonly ShowRepository        $showRepository,
        private readonly EpisodeViewRepository $episodeViewRepository
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

        return $this->render("season.twig", [
            "show" => $show,
            "season" => $season,
            "pagination" => [
                "previousSeason" => $season->getPreviousSeason(),
                "nextSeason" => $season->getNextSeason()
            ]
        ]);
    }

    #[Route("/shows/{show}/seasons/{seasonNumber}/episodes/{episodeNumber}", name: "episodePage")]
    public function getEpisodePage(Show $show, int $seasonNumber, int $episodeNumber): Response
    {
        $season = $show->getSeason($seasonNumber);
        if ($season === null) {
            throw new NotFoundHttpException("Season not found");
        }

        $episode = $season->getEpisode($episodeNumber);
        if ($episode === null) {
            throw new NotFoundHttpException("Episode not found");
        }

        return $this->render("episode.twig", [
            "show" => $show,
            "season" => $season,
            "episode" => $episode,
            "pagination" => [
                "previousEpisode" => $episode->getPreviousEpisode(),
                "nextEpisode" => $episode->getNextEpisode()
            ]
        ]);
    }

    #[Route("/shows/{showId}/seasons/{seasonNumber}/episodes/{episodeNumber}/views", name: "addEpisodeView", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function addView(int $showId, int $seasonNumber, int $episodeNumber, EntityManagerInterface $entityManager): Response
    {
        /**
         * @var $show Show
         */
        $show = $this->showRepository->find($showId);
        if ($show === null) {
            throw new NotFoundHttpException("Show not found");
        }

        $season = $show->getSeason($seasonNumber);
        if ($season === null) {
            throw new NotFoundHttpException("Season not found");
        }

        $episode = $season->getEpisode($episodeNumber);
        if ($episode === null) {
            throw new NotFoundHttpException("Episode not found");
        }

        $episodeView = new EpisodeView;
        $episodeView->setEpisode($episode);
        $episodeView->setUser($this->getUser());
        $episodeView->setDateTime(new DateTime);

        $entityManager->persist($episodeView);
        $entityManager->flush();

        return new Response("View added to database");
    }

    #[Route("/shows/{show}/seasons/{seasonNumber}/episodes/{episodeNumber}/views/all", name: "removeEpisodeViews", methods: ["DELETE"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function removeViewsByEpisode(Show $show, int $seasonNumber, int $episodeNumber, EntityManagerInterface $entityManager): Response
    {
        $season = $show->getSeason($seasonNumber);
        if ($season === null) {
            throw new NotFoundHttpException("Season not found");
        }

        $episode = $season->getEpisode($episodeNumber);
        if ($episode === null) {
            throw new NotFoundHttpException("Episode not found");
        }

        $views = $episode->getViewsForUser($this->getUser());

        foreach ($views as $view) {
            $entityManager->remove($view);
        }

        $entityManager->flush();

        return new Response("Views removed from database");
    }

    #[Route("/shows/{show}/seasons/{seasonNumber}/episodes/{episodeNumber}/views/{entryId}", name: "removeEpisodeViewById", methods: ["DELETE"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function removeViewById(Show $show, int $seasonNumber, int $episodeNumber, int $entryId, EntityManagerInterface $entityManager): Response
    {
        $season = $show->getSeason($seasonNumber);
        if ($season === null) {
            throw new NotFoundHttpException("Season not found");
        }

        $episode = $season->getEpisode($episodeNumber);
        if ($episode === null) {
            throw new NotFoundHttpException("Episode not found");
        }

        $view = $this->episodeViewRepository->findOneBy(["id" => $entryId, "user" => $this->getUser()]);
        if ($view === null) {
            throw new NotFoundHttpException("View not found");
        }

        $entityManager->remove($view);
        $entityManager->flush();

        return new Response("View removed from database");
    }
}