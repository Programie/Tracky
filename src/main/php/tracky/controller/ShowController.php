<?php
namespace tracky\controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use tracky\datetime\DateTime;
use tracky\model\EpisodeView;
use tracky\model\Show;
use tracky\orm\ShowRepository;
use tracky\orm\UserRepository;
use tracky\orm\ViewRepository;

class ShowController extends AbstractController
{
    public function __construct(
        private readonly ShowRepository $showRepository,
        private readonly ViewRepository $viewRepository
    )
    {
    }

    #[Route("/shows", name: "showsPage")]
    public function getShowsPage(): Response
    {
        return $this->render("shows/shows.twig", [
            "shows" => $this->showRepository->findBy([], ["title" => "asc"])
        ]);
    }

    #[Route("/shows/{id}", name: "showOverviewPage")]
    public function getShowOverviewPage(Show $show): Response
    {
        return $this->redirectToRoute("showSeasonsPage", ["id" => $show->getId()]);
    }

    #[Route("/shows/{id}/seasons", name: "showSeasonsPage")]
    public function getSeasonsPage(Show $show): Response
    {
        return $this->render("shows/seasons.twig", [
            "show" => $show
        ]);
    }

    #[Route("/shows/{id}/random-episodes", name: "randomEpisodesPage")]
    public function getRandomEpisodesPage(Show $show): Response
    {
        return $this->render("shows/episodes.twig", [
            "show" => $show,
            "title" => "shows.random-episodes",
            "episodes" => $show->getRandomEpisodes(10)
        ]);
    }

    #[Route("/shows/{id}/most-watched", name: "mostWatchedEpisodesPage")]
    #[IsGranted("IS_AUTHENTICATED")]
    public function getMostWatchedEpisodesPage(Show $show, Request $request, UserRepository $userRepository): Response
    {
        return $this->render("shows/episodes.twig", [
            "show" => $show,
            "title" => "shows.most-watched-episodes",
            "episodes" => $show->getMostOrLeastWatchedEpisodes($this->getUser(), 10)
        ]);
    }

    #[Route("/shows/{id}/least-watched", name: "leastWatchedEpisodesPage")]
    #[IsGranted("IS_AUTHENTICATED")]
    public function getLeastWatchedEpisodesPage(Show $show, Request $request, UserRepository $userRepository): Response
    {
        return $this->render("shows/episodes.twig", [
            "show" => $show,
            "title" => "shows.least-watched-episodes",
            "episodes" => $show->getMostOrLeastWatchedEpisodes($this->getUser(), 10, true)
        ]);
    }

    #[Route("/shows/{id}/seasons/{number}", name: "seasonPage")]
    public function getSeasonPage(Show $show, int $number): Response
    {
        $season = $show->getSeason($number);
        if ($season === null) {
            throw new NotFoundHttpException("Season not found!");
        }

        return $this->render("shows/season.twig", [
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

        return $this->render("shows/episode.twig", [
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

        $view = $this->viewRepository->findOneBy(["id" => $entryId, "user" => $this->getUser()], type: "episode");
        if ($view === null) {
            throw new NotFoundHttpException("View not found");
        }

        $entityManager->remove($view);
        $entityManager->flush();

        return new Response("View removed from database");
    }
}