<?php
namespace tracky\console;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use tracky\dataprovider\Helper;
use tracky\ImageFetcher;
use tracky\model\MovieSet;
use tracky\model\Show;
use tracky\orm\MovieSetRepository;
use tracky\orm\ShowRepository;

#[AsCommand(name: "update-data", description: "Fetch updates of shows and movie sets needing an update")]
class UpdateDataCommand extends Command
{
    public function __construct(
        private readonly Helper                 $dataProviderHelper,
        private readonly ShowRepository         $showRepository,
        private readonly MovieSetRepository     $movieSetRepository,
        private readonly ImageFetcher           $imageFetcher,
        private readonly EntityManagerInterface $entityManager,
        private readonly int                    $showFetchInterval,
        private readonly int                    $movieSetFetchInterval,
        private readonly bool                   $downloadAllImages
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption("force", "f", InputOption::VALUE_NONE, "Update all shows and movie sets even if they don't need an update");
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /**
         * @var Show
         */
        foreach ($this->showRepository->findAll() as $show) {
            if (!$input->getOption("force") and !$show->needsUpdate($this->showFetchInterval)) {
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

        /**
         * @var MovieSet
         */
        foreach ($this->movieSetRepository->findAll() as $movieSet) {
            if (!$input->getOption("force") and !$movieSet->needsUpdate($this->movieSetFetchInterval)) {
                continue;
            }

            $output->writeln(sprintf("Fetching data for movie set: %s", $movieSet->getTitle()));

            $dataProvider = $this->dataProviderHelper->getProviderByEntry($movieSet);
            $dataProvider->fetchMovieSet($movieSet);

            $this->entityManager->persist($movieSet);
            $this->entityManager->flush();

            if ($this->downloadAllImages) {
                $movieSet->fetchPosterImages($this->imageFetcher, true);
            }
        }

        return self::SUCCESS;
    }
}
