<?php
namespace tracky\controller;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
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
use tracky\orm\ViewRepository;

class ShowController extends AbstractController
{
    public function __construct(
        private readonly ShowRepository $showRepository,
        private readonly ViewRepository $viewRepository,
        private readonly int $maxEpisodes,
    )
    {
    }

    #[Route("/shows/{show}.jpg", name: "getShowImage")]
    public function getShowImage(Show $show, ImageFetcher $imageFetcher): Response
    {
        return $this->returnImage($show, $imageFetcher);
    }

    #[Route("/shows/{show}/seasons/{seasonNumber}.jpg", name: "getSeasonImage")]
    public function getSeasonImage(Show $show, int $seasonNumber, ImageFetcher $imageFetcher): Response
    {
        $season = $show->getSeason($seasonNumber);
        if ($season === null) {
            throw new NotFoundHttpException("Season not found");
        }

        return $this->returnImage($season, $imageFetcher);
    }

    #[Route("/shows/{show}/seasons/{seasonNumber}/episodes/{episodeNumber}.jpg", name: "getEpisodeImage")]
    public function getEpisodeImage(Show $show, int $seasonNumber, int $episodeNumber, ImageFetcher $imageFetcher): Response
    {
        $season = $show->getSeason($seasonNumber);
        if ($season === null) {
            throw new NotFoundHttpException("Season not found");
        }

        $episode = $season->getEpisode($episodeNumber);
        if ($episode === null) {
            throw new NotFoundHttpException("Episode not found");
        }

        return $this->returnImage($episode, $imageFetcher);
    }

    #[Route("/shows", name: "showsPage")]
    public function getShowsPage(): Response
    {
        return $this->render("shows/shows.twig", [
            "shows" => $this->showRepository->findAllWithEpisodes()
        ]);
    }

    #[Route("/shows/{show}", name: "showOverviewPage", methods: ["GET"])]
    public function getShowOverviewPage(Show $show): Response
    {
        return $this->redirectToRoute("showSeasonsPage", ["show" => $show->getId()]);
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

    #[Route("/shows/{show}/seasons", name: "showSeasonsPage")]
    public function getSeasonsPage(int $show): Response
    {
        return $this->render("shows/seasons.twig", [
            "show" => $this->showRepository->findByIdWithEpisodes($show)
        ]);
    }

    #[Route("/shows/{show}/random-episodes", name: "randomEpisodesPage")]
    public function getRandomEpisodesPage(int $show): Response
    {
        $show = $this->showRepository->findByIdWithEpisodes($show);

        return $this->render("shows/episodes.twig", [
            "show" => $show,
            "title" => "shows.random-episodes",
            "episodes" => $show->getRandomEpisodes($this->maxEpisodes)
        ]);
    }

    #[Route("/shows/{show}/latest-watched", name: "latestWatchedEpisodesPage")]
    #[IsGranted("IS_AUTHENTICATED")]
    public function getLatestWatchedEpisodesPage(int $show): Response
    {
        $show = $this->showRepository->findByIdWithEpisodesAndViews($show, $this->getUser()->getId());

        return $this->render("shows/episodes.twig", [
            "show" => $show,
            "title" => "shows.latest-watched-episodes",
            "episodes" => $show->getLatestWatchedEpisodes($this->getUser(), $this->maxEpisodes)
        ]);
    }

    #[Route("/shows/{show}/most-watched", name: "mostWatchedEpisodesPage")]
    #[IsGranted("IS_AUTHENTICATED")]
    public function getMostWatchedEpisodesPage(int $show): Response
    {
        $show = $this->showRepository->findByIdWithEpisodesAndViews($show, $this->getUser()->getId());

        return $this->render("shows/episodes.twig", [
            "show" => $show,
            "title" => "shows.most-watched-episodes",
            "episodes" => $show->getMostOrLeastWatchedEpisodes($this->getUser(), $this->maxEpisodes)
        ]);
    }

    #[Route("/shows/{show}/least-watched", name: "leastWatchedEpisodesPage")]
    #[IsGranted("IS_AUTHENTICATED")]
    public function getLeastWatchedEpisodesPage(int $show): Response
    {
        $show = $this->showRepository->findByIdWithEpisodesAndViews($show, $this->getUser()->getId());

        return $this->render("shows/episodes.twig", [
            "show" => $show,
            "title" => "shows.least-watched-episodes",
            "episodes" => $show->getMostOrLeastWatchedEpisodes($this->getUser(), $this->maxEpisodes, true)
        ]);
    }

    #[Route("/shows/{show}/unwatched", name: "unwatchedEpisodesPage")]
    #[IsGranted("IS_AUTHENTICATED")]
    public function getUnwatchedEpisodesPage(int $show): Response
    {
        $show = $this->showRepository->findByIdWithEpisodesAndViews($show, $this->getUser()->getId());

        return $this->render("shows/episodes.twig", [
            "show" => $show,
            "title" => "shows.unwatched-episodes",
            "episodes" => $show->getUnwatchedEpisodes($this->getUser())
        ]);
    }

    #[Route("/shows/{show}/seasons/{number}", name: "seasonPage")]
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

    #[Route("/shows/{show}/seasons/{seasonNumber}/episodes/{episodeNumber}/views", name: "addEpisodeView", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function addView(Show $show, int $seasonNumber, int $episodeNumber, Request $request, EntityManagerInterface $entityManager): Response
    {
        $season = $show->getSeason($seasonNumber);
        if ($season === null) {
            throw new NotFoundHttpException("Season not found");
        }

        $episode = $season->getEpisode($episodeNumber);
        if ($episode === null) {
            throw new NotFoundHttpException("Episode not found");
        }

        try {
            $dateTime = new DateTime($request->getPayload()->get("timestamp"));
        } catch (Exception) {
            throw new BadRequestException("Invalid payload");
        }

        $episodeView = new EpisodeView;
        $episodeView->setEpisode($episode);
        $episodeView->setUser($this->getUser());
        $episodeView->setDateTime($dateTime);

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
            if ($entry instanceof Episode) {
                return $this->returnImage($entry->getSeason(), $imageFetcher);
            } elseif ($entry instanceof Season) {
                return $this->returnImage($entry->getShow(), $imageFetcher);
            } else {
                throw new NotFoundHttpException("Image not available");
            }
        }

        $path = $imageFetcher->get($url);
        if ($path === null) {
            throw new RuntimeException("Unable to fetch image");
        }

        return $this->file($path, null, ResponseHeaderBag::DISPOSITION_INLINE);
    }
}
