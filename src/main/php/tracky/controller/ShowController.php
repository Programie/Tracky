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
use tracky\model\Season;
use tracky\model\Show;
use tracky\model\View;
use tracky\orm\ShowRepository;
use tracky\orm\ViewRepository;
use tracky\ViewType;
use tracky\watchstats\WatchStatsProvider;

class ShowController extends AbstractController
{
    public function __construct(
        private readonly ShowRepository $showRepository,
        private readonly ViewRepository $viewRepository,
        private readonly int $maxEpisodes,
    )
    {
    }

    #[Route("/shows/{show}.jpg", name: "shows_show_image")]
    public function getShowImage(Show $show, ImageFetcher $imageFetcher): Response
    {
        return $this->returnImage($show, $imageFetcher);
    }

    #[Route("/shows/{show}/seasons/{seasonNumber}.jpg", name: "shows_season_image")]
    public function getSeasonImage(Show $show, int $seasonNumber, ImageFetcher $imageFetcher): Response
    {
        $season = $show->getSeason($seasonNumber);
        if ($season === null) {
            throw new NotFoundHttpException("Season not found");
        }

        return $this->returnImage($season, $imageFetcher);
    }

    #[Route("/shows/{show}/seasons/{seasonNumber}/episodes/{episodeNumber}.jpg", name: "shows_episode_image")]
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

    #[Route("/shows", name: "shows_page")]
    public function getShowsPage(): Response
    {
        return $this->render("shows/shows.twig", [
            "shows" => $this->showRepository->findAllWithEpisodes()
        ]);
    }

    #[Route("/shows/{show}", name: "shows_show_page", methods: ["GET"])]
    public function getShowOverviewPage(Show $show): Response
    {
        return $this->redirectToRoute("shows_seasons_page", ["show" => $show->getId()]);
    }

    #[Route("/shows/{show}", name: "shows_show_remove_action", methods: ["DELETE"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function removeShow(Show $show, ViewRepository $viewRepository, EntityManagerInterface $entityManager): Response
    {
        // Make sure no episode view exists for this show
        foreach ($show->getSeasons() as $season) {
            foreach ($season->getEpisodes() as $episode) {
                if ($viewRepository->count(["item" => $episode->getId()], type: ViewType::EPISODE)) {
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

    #[Route("/shows/{show}/seasons", name: "shows_seasons_page")]
    public function getSeasonsPage(int $show): Response
    {
        return $this->render("shows/seasons.twig", [
            "show" => $this->showRepository->findByIdWithEpisodes($show)
        ]);
    }

    #[Route("/shows/{show}/random-episodes", name: "shows_random_episodes_page")]
    public function getRandomEpisodesPage(int $show): Response
    {
        $show = $this->showRepository->findByIdWithEpisodes($show);

        return $this->render("shows/episodes.twig", [
            "show" => $show,
            "title" => "shows.random-episodes",
            "episodes" => $show->getRandomEpisodes($this->maxEpisodes)
        ]);
    }

    #[Route("/shows/{show}/latest-watched", name: "shows_latest_watched_episodes_page")]
    #[IsGranted("IS_AUTHENTICATED")]
    public function getLatestWatchedEpisodesPage(int $show, WatchStatsProvider $watchStatsProvider): Response
    {
        $show = $this->showRepository->findByIdWithEpisodes($show);

        return $this->render("shows/episodes.twig", [
            "show" => $show,
            "title" => "shows.latest-watched-episodes",
            "episodes" => array_map(fn($item) => $item[0], $show->getLatestWatchedEpisodes($watchStatsProvider, $this->maxEpisodes))
        ]);
    }

    #[Route("/shows/{show}/most-watched", name: "shows_most_watched_episodes_page")]
    #[IsGranted("IS_AUTHENTICATED")]
    public function getMostWatchedEpisodesPage(int $show, WatchStatsProvider $watchStatsProvider): Response
    {
        $show = $this->showRepository->findByIdWithEpisodes($show);

        return $this->render("shows/episodes.twig", [
            "show" => $show,
            "title" => "shows.most-watched-episodes",
            "episodes" => array_map(fn($item) => $item[0], $show->getMostOrLeastWatchedEpisodes($watchStatsProvider, $this->maxEpisodes, false))
        ]);
    }

    #[Route("/shows/{show}/least-watched", name: "shows_least_watched_episodes_page")]
    #[IsGranted("IS_AUTHENTICATED")]
    public function getLeastWatchedEpisodesPage(int $show, WatchStatsProvider $watchStatsProvider): Response
    {
        $show = $this->showRepository->findByIdWithEpisodes($show);

        return $this->render("shows/episodes.twig", [
            "show" => $show,
            "title" => "shows.least-watched-episodes",
            "episodes" => array_map(fn($item) => $item[0], $show->getMostOrLeastWatchedEpisodes($watchStatsProvider, $this->maxEpisodes, true))
        ]);
    }

    #[Route("/shows/{show}/unwatched", name: "shows_unwatched_episodes_page")]
    #[IsGranted("IS_AUTHENTICATED")]
    public function getUnwatchedEpisodesPage(int $show, WatchStatsProvider $watchStatsProvider): Response
    {
        $show = $this->showRepository->findByIdWithEpisodes($show);

        return $this->render("shows/episodes.twig", [
            "show" => $show,
            "title" => "shows.unwatched-episodes",
            "episodes" => $show->getUnwatchedEpisodes($watchStatsProvider)
        ]);
    }

    #[Route("/shows/{show}/seasons/{number}", name: "shows_season_page")]
    public function getSeasonPage(Show $show, int $number): Response
    {
        $season = $show->getSeason($number);
        if ($season === null) {
            throw new NotFoundHttpException("Season not found!");
        }

        return $this->render("shows/season.twig", [
            "show" => $show,
            "season" => $season,
            "seasons" => $show->getSeasons()
        ]);
    }

    #[Route("/shows/{show}/seasons/{seasonNumber}/episodes/{episodeNumber}/views", name: "shows_add_episode_view_action", methods: ["POST"])]
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

        $view = new View;
        $view->setItem($episode);
        $view->setUser($this->getUser());
        $view->setDateTime($dateTime);

        $entityManager->persist($view);
        $entityManager->flush();

        return new Response("View added to database");
    }

    #[Route("/shows/{show}/seasons/{seasonNumber}/episodes/{episodeNumber}/views/all", name: "shows_remove_episode_view_action", methods: ["DELETE"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function removeViewsByEpisode(Show $show, int $seasonNumber, int $episodeNumber, ViewRepository $viewRepository, EntityManagerInterface $entityManager): Response
    {
        $season = $show->getSeason($seasonNumber);
        if ($season === null) {
            throw new NotFoundHttpException("Season not found");
        }

        $episode = $season->getEpisode($episodeNumber);
        if ($episode === null) {
            throw new NotFoundHttpException("Episode not found");
        }

        $views = $viewRepository->findBy(["item" => $episode->getId(), "user" => $this->getUser(), "type" => ViewType::EPISODE->value]);

        foreach ($views as $view) {
            $entityManager->remove($view);
        }

        $entityManager->flush();

        return new Response("Views removed from database");
    }

    #[Route("/shows/{show}/seasons/{seasonNumber}/episodes/{episodeNumber}/views/{entryId}", name: "shows_remove_episode_view_by_id_action", methods: ["DELETE"])]
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

        $view = $this->viewRepository->findOneBy(["id" => $entryId, "user" => $this->getUser(), "type" => ViewType::EPISODE->value]);
        if ($view === null) {
            throw new NotFoundHttpException("View not found");
        }

        if ($view->getItem() !== $episode->getId()) {
            throw new NotFoundHttpException("View item does not match episode");
        }

        $entityManager->remove($view);
        $entityManager->flush();

        return new Response("View removed from database");
    }

    private function returnImage(Show|Season|Episode $entry, ImageFetcher $imageFetcher): Response
    {
        $url = $entry->getPosterImageUrl();
        if ($url === null) {
            if ($entry instanceof Season) {
                return $this->returnImage($entry->getShow(), $imageFetcher);
            }

            throw new NotFoundHttpException("Image not available");
        }

        $path = $imageFetcher->get($url);
        if ($path === null) {
            throw new RuntimeException("Unable to fetch image");
        }

        return $this->file($path, null, ResponseHeaderBag::DISPOSITION_INLINE);
    }
}
