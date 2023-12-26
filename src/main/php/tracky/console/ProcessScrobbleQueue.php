<?php
namespace tracky\console;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use tracky\model\ScrobbleQueueItem;
use tracky\orm\ScrobbleQueueItemRepository;
use tracky\scrobbler\Scrobbler;

#[AsCommand(name: "process-scrobble-queue", description: "Process data currently stored in scrobble queue")]
class ProcessScrobbleQueue extends Command
{
    public function __construct(
        private readonly ScrobbleQueueItemRepository $scrobbleQueueItemRepository,
        private readonly Scrobbler                   $scrobbler,
        private readonly EntityManagerInterface      $entityManager
    )
    {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /**
         * @var $queueItems ScrobbleQueueItem[]
         */
        $queueItems = $this->scrobbleQueueItemRepository->findAll();

        try {
            foreach ($queueItems as $queueItem) {
                $output->writeln(sprintf("#%d: %s", $queueItem->getId(), $this->scrobbler->addViewFromQueue($queueItem)));

                $this->entityManager->remove($queueItem);
            }
        } finally {
            $this->entityManager->flush();
        }

        return self::SUCCESS;
    }
}