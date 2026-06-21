<?php
namespace tracky\watchstats;

use Symfony\Bundle\SecurityBundle\Security;
use tracky\model\Episode;
use tracky\model\Movie;
use tracky\orm\ViewRepository;
use tracky\ViewType;

class WatchStatsProvider
{
    /**
     * @var array<value-of<ViewType>, WatchStatsCollection>
     */
    private array $collections;

    public function __construct(
        private readonly ViewRepository $viewRepository,
        private readonly Security $security
    ) {}

    public function getStatsForType(ViewType $type): ?WatchStatsCollection
    {
        if ($this->collections[$type->value] ?? null !== null) {
            return $this->collections[$type->value];
        }

        $user = $this->security->getUser();
        if ($user === null) {
            return null;
        }

        return $this->collections[$type->value] = $this->viewRepository->getWatchStatsForUser($user, $type);
    }

    public function getItemStats(Episode|Movie $item): ?ItemWatchStats
    {
        return $this->getStatsForType($item->getViewType())?->getStatsForItem($item);
    }
}
