<?php
namespace tracky\watchstats;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use tracky\model\Episode;
use tracky\model\Movie;
use tracky\model\User;
use tracky\model\View;
use tracky\orm\ViewRepository;
use tracky\ViewType;

class WatchStatsProvider
{
    private ?User $user;
    /**
     * @var array<value-of<ViewType>, WatchStatsCollection>
     */
    private array $perTypeCollection;

    public function __construct(
        private readonly ViewRepository $viewRepository,

        #[Autowire(service: "Symfony\\Bundle\\SecurityBundle\\Security")]
        User|Security $userOrSecurity
    )
    {
        if ($userOrSecurity instanceof Security) {
            $this->user = $userOrSecurity->getUser();
        } else {
            $this->user = $userOrSecurity;
        }
    }

    public function getStatsForType(ViewType $type): ?WatchStatsCollection
    {
        if ($this->user === null) {
            return null;
        }

        if (!isset($this->perTypeCollection[$type->value])) {
            $this->perTypeCollection[$type->value] = $this->viewRepository->getWatchStatsForUser($this->user, $type);
        }

        return $this->perTypeCollection[$type->value];
    }

    public function getItemStats(Episode|Movie|int $item): ?ItemWatchStats
    {
        return $this->getStatsForType($item->getViewType())?->getStatsForItem($item);
    }

    public function getItemStatsByView(View $view): ?ItemWatchStats
    {
        return $this->getStatsForType($view->getType())?->getStatsForItem($view->getItem());
    }
}
