<?php
namespace tracky\console;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use tracky\dataprovider\Helper;
use tracky\dataprovider\Provider;
use tracky\orm\MovieRepository;
use UnexpectedValueException;

#[AsCommand(name: "fetch-movie-data", description: "Fetch movie data from the configured data provider")]
class FetchMovieDataCommand extends Command
{
    private Provider $dataProvider;

    public function __construct(
        Helper                                  $dataProviderHelper,
        private readonly MovieRepository        $movieRepository,
        private readonly EntityManagerInterface $entityManager
    )
    {
        parent::__construct();

        $this->dataProvider = $dataProviderHelper->getProviderByType(Helper::TYPE_MOVIE);
    }

    protected function configure(): void
    {
        $this->addArgument("movie", InputArgument::REQUIRED, "The movie ID");
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $movieId = (int)$input->getArgument("movie");

        $movie = $this->movieRepository->findOneBy(["id" => $movieId]);
        if ($movie === null) {
            throw new UnexpectedValueException(sprintf("Movie with ID %d not found", $movieId));
        }

        $output->writeln(sprintf("Fetching data for movie %d: %s", $movieId, $movie->getTitle()));

        $this->dataProvider->fetchMovie($movie);

        $this->entityManager->persist($movie);
        $this->entityManager->flush();

        $output->writeln("Done");

        return self::SUCCESS;
    }
}