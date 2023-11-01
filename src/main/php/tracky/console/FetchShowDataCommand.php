<?php
namespace tracky\console;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use tracky\dataprovider\Provider;
use tracky\model\Show;
use tracky\orm\ShowRepository;

class FetchShowDataCommand extends Command
{
    public function __construct(
        private readonly Provider               $dataProvider,
        private readonly ShowRepository         $showRepository,
        private readonly EntityManagerInterface $entityManager
    )
    {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->setName("fetch-show-data");
        $this->setDescription("Fetch show data from the configured data provider");
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /**
         * @var $show Show
         */
        foreach ($this->showRepository->findAll() as $show) {
            $output->writeln(sprintf("Fetching data for show: %s", $show->getTitle()));

            $this->dataProvider->fetchShow($show, true);

            $this->entityManager->persist($show);
        }

        $this->entityManager->flush();

        $output->writeln("Done");

        return self::SUCCESS;
    }
}