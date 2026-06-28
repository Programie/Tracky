<?php
namespace tracky;

use tracky\model\Episode;
use tracky\model\Movie;
use tracky\model\View;
use tracky\orm\EpisodeRepository;
use tracky\orm\MovieRepository;
use tracky\watchstats\WatchStatsProvider;

class HistoryEntry
{
    public function __construct(
        private readonly View $view,
        private readonly Episode|Movie $item,
        private readonly WatchStatsProvider $watchStatsProvider
    ) {}

    public function getView(): View
    {
        return $this->view;
    }

    public function getItem(): Episode|Movie
    {
        return $this->item;
    }

    public function getViewCount(): int
    {
        return $this->watchStatsProvider->getItemStatsByView($this->view)->getCount();
    }

    /**
     * @return list<HistoryEntry>
     */
    public static function getFromViews(array $views, EpisodeRepository $episodeRepository, MovieRepository $movieRepository, WatchStatsProvider $watchStatsProvider): array
    {
        $perTypeItems = [];

        // Split up list of views to list of items per type
        foreach ($views as $view) {
            $type = $view->getType()->value;

            if (!isset($perTypeItems[$type])) {
                $perTypeItems[$type] = [];
            }

            $perTypeItems[$type][] = $view->getItem();
        }

        // Fetch items per type
        foreach ($perTypeItems as $type => $items) {
            $items = array_unique($items);

            switch ($type) {
                case ViewType::EPISODE->value:
                    $items = $episodeRepository->findByIds($items);
                    break;
                case ViewType::MOVIE->value:
                    $items = $movieRepository->findByIds($items);
                    break;
            }

            $perTypeItems[$type] = array_combine(array_map(fn(Episode|Movie $item) => $item->getId(), $items), $items);
        }

        $historyEntries = [];

        foreach ($views as $view) {
            $historyEntries[] = new HistoryEntry($view, $perTypeItems[$view->getType()->value][$view->getItem()], $watchStatsProvider);
        }

        return $historyEntries;
    }
}
