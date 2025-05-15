<?php
namespace tracky\console;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use tracky\dataprovider\Helper;
use tracky\ImageFetcher;
use tracky\model\Show;
use tracky\orm\ShowRepository;

#[AsCommand(name: "update-data", description: "Fetch new episodes and seasons for all shows needing an update")]
class UpdateDataCommand extends Command
{
    public function __construct(
        private readonly Helper                 $dataProviderHelper,
        private readonly ShowRepository         $showRepository,
        private readonly ImageFetcher           $imageFetcher,
        private readonly EntityManagerInterface $entityManager,
        private readonly int                    $showFetchInterval,
        private readonly bool                   $downloadAllImages
    )
    {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /**
         * @var $show Show
         */
        foreach ($this->showRepository->findAll() as $show) {
            if (!$show->needsUpdate($this->showFetchInterval)) {
                continue;
            }

            $output->writeln(sprintf("Fetching data for show: %s", $show->getTitle()));

            $dataProvider = $this->dataProviderHelper->getProviderByEntry($show);
            $dataProvider->fetchShow($show, true);

            $this->entityManager->persist($show);
            $this->entityManager->flush();

            if ($this->downloadAllImages) {
                $show->fetchPosterImages($this->imageFetcher, true, true);
            }
        }

        return self::SUCCESS;
    }
}
