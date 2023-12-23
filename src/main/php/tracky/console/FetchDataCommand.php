<?php
namespace tracky\console;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use tracky\dataprovider\Helper;
use tracky\orm\MovieRepository;
use tracky\orm\ShowRepository;
use UnexpectedValueException;

#[AsCommand(name: "fetch-data", description: "Fetch show or movie data from the configured data provider")]
class FetchDataCommand extends Command
{
    public function __construct(
        private readonly Helper                 $dataProviderHelper,
        private readonly MovieRepository        $movieRepository,
        private readonly ShowRepository         $showRepository,
        private readonly EntityManagerInterface $entityManager
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument("type", InputArgument::REQUIRED, "The type (movie or show)");
        $this->addArgument("id", InputArgument::REQUIRED, "The movie or show ID");
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = strtolower(trim($input->getArgument("type")));
        $id = (int)$input->getArgument("id");

        switch ($type) {
            case Helper::TYPE_SHOW:
                $dataProvider = $this->dataProviderHelper->getProviderByType(Helper::TYPE_SHOW);

                $show = $this->showRepository->findOneBy(["id" => $id]);
                if ($show === null) {
                    throw new UnexpectedValueException(sprintf("Show with ID %d not found", $show));
                }

                $output->writeln(sprintf("Fetching data for show %d (%s) using provider %s", $id, $show->getTitle(), $dataProvider::class));

                if (!$dataProvider->fetchShow($show, true)) {
                    throw new UnexpectedValueException("Fetching show failed");
                }

                $this->entityManager->persist($show);
                break;
            case Helper::TYPE_MOVIE:
                $dataProvider = $this->dataProviderHelper->getProviderByType(Helper::TYPE_MOVIE);

                $movie = $this->movieRepository->findOneBy(["id" => $id]);
                if ($movie === null) {
                    throw new UnexpectedValueException(sprintf("Movie with ID %d not found", $movie));
                }

                $output->writeln(sprintf("Fetching data for movie %d (%s) using provider %s", $id, $movie->getTitle(), $dataProvider::class));

                if (!$dataProvider->fetchMovie($movie)) {
                    throw new UnexpectedValueException("Fetching movie failed");
                }

                $this->entityManager->persist($movie);
                break;
            default:
                throw new UnexpectedValueException(sprintf("Unknown type: %s", $type));
        }

        $this->entityManager->flush();

        $output->writeln("Done");

        return self::SUCCESS;
    }
}