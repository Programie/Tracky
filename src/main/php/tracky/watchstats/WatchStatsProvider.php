<?php
namespace tracky\watchstats;

use Symfony\Bundle\SecurityBundle\Security;
use tracky\model\Episode;
use tracky\model\Movie;
use tracky\model\User;
use tracky\orm\ViewRepository;
use tracky\ViewType;

class WatchStatsProvider
{
    /**
     * @var array<int, array<<value-of<ViewType>, WatchStatsCollection>>
     */
    private array $perUserCollections;

    public function __construct(
        private readonly ViewRepository $viewRepository,
        private readonly Security $security
    ) {}

    public function getStatsForType(ViewType $type, ?User $user = null): ?WatchStatsCollection
    {
        if ($user === null) {
            /**
             * @var User
             */
            $user = $this->security->getUser();
            if ($user === null) {
                return null;
            }
        }

        $userId = $user->getId();

        if (!isset($this->perUserCollections[$userId])) {
            $this->perUserCollections[$userId] = [];
        }

        if (isset($this->perUserCollections[$userId][$type->value])) {
            return $this->perUserCollections[$userId][$type->value];
        }

        return $this->perUserCollections[$userId][$type->value] = $this->viewRepository->getWatchStatsForUser($user, $type);
    }

    public function getItemStats(Episode|Movie $item, ?User $user = null): ?ItemWatchStats
    {
        return $this->getStatsForType($item->getViewType(), $user)?->getStatsForItem($item);
    }
}
