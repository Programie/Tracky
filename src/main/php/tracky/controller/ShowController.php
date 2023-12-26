<?php
namespace tracky\controller;

use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use tracky\datetime\DateTime;
use tracky\ImageFetcher;
use tracky\model\Episode;
use tracky\model\EpisodeView;
use tracky\model\Season;
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

    #[Route("/shows/{show}.jpg", name: "getShowImage")]
    public function getShowImage(Show $show, ImageFetcher $imageFetcher): Response
    {
        return $this->returnImage($show, $imageFetcher);
    }

    #[Route("/shows/{show}/seasons/{season}.jpg", name: "getSeasonImage")]
    public function getSeasonImage(Show $show, int $season, ImageFetcher $imageFetcher): Response
    {
        $season = $show->getSeason($season);
        if ($season === null) {
            throw new NotFoundHttpException("Season not found");
        }

        return $this->returnImage($season, $imageFetcher);
    }

    #[Route("/shows/{show}/seasons/{season}/episodes/{episode}.jpg", name: "getEpisodeImage")]
    public function getEpisodeImage(Show $show, int $season, int $episode, ImageFetcher $imageFetcher): Response
    {
        $season = $show->getSeason($season);
        if ($season === null) {
            throw new NotFoundHttpException("Season not found");
        }

        $episode = $season->getEpisode($episode);
        if ($episode === null) {
            throw new NotFoundHttpException("Episode not found");
        }

        return $this->returnImage($episode, $imageFetcher);
    }

    #[Route("/shows", name: "showsPage")]
    public function getShowsPage(): Response
    {
        return $this->render("shows/shows.twig", [
            "shows" => $this->showRepository->findBy([], ["title" => "asc"])
        ]);
    }

    #[Route("/shows/{id}", name: "showOverviewPage", methods: ["GET"])]
    public function getShowOverviewPage(Show $show): Response
    {
        return $this->redirectToRoute("showSeasonsPage", ["id" => $show->getId()]);
    }

    #[Route("/shows/{show}", name: "removeShow", methods: ["DELETE"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function removeShow(Show $show, EntityManagerInterface $entityManager): Response
    {
        $viewRepository = $entityManager->getRepository(EpisodeView::class);

        // Make sure no episode view exists for this show
        foreach ($show->getSeasons() as $season) {
            foreach ($season->getEpisodes() as $episode) {
                if ($viewRepository->count(["item" => $episode->getId()], type: "episode")) {
                    return $this->json([
                        "error" => "view-exists"
                    ], 409);
                }
            }
        }

        $entityManager->remove($show);
        $entityManager->flush();

        return new Response("Show removed from database");
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

    private function returnImage(Show|Season|Episode $entry, ImageFetcher $imageFetcher): Response
    {
        $url = $entry->getPosterImageUrl();
        if ($url === null) {
            throw new NotFoundHttpException("Image not available");
        }

        $path = $imageFetcher->get($url);
        if ($path === null) {
            throw new RuntimeException("Unable to fetch image");
        }

        return $this->file($path, null, ResponseHeaderBag::DISPOSITION_INLINE);
    }
}