<?php
namespace tracky\console;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use tracky\model\ScrobbleQueue;
use tracky\orm\ScrobbleQueueRepository;
use tracky\scrobbler\Scrobbler;

class ProcessScrobbleQueue extends Command
{
    public function __construct(
        private readonly ScrobbleQueueRepository $scrobbleQueueRepository,
        private readonly Scrobbler               $scrobbler,
        private readonly EntityManagerInterface  $entityManager
    )
    {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->setName("process-scrobble-queue");
        $this->setDescription("Process data currently stored in scrobble queue");
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var $queueItems ScrobbleQueue[]
         */
        $queueItems = $this->scrobbleQueueRepository->findAll();

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