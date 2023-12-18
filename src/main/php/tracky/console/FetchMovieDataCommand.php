<?php
namespace tracky\console;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use tracky\dataprovider\Helper;
use tracky\dataprovider\Provider;
use tracky\model\Movie;
use tracky\orm\MovieRepository;

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

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /**
         * @var $movie Movie
         */
        foreach ($this->movieRepository->findAll() as $movie) {
            $output->writeln(sprintf("Fetching data for movie: %s", $movie->getTitle()));

            $this->dataProvider->fetchMovie($movie);

            $this->entityManager->persist($movie);
        }

        $this->entityManager->flush();

        $output->writeln("Done");

        return self::SUCCESS;
    }
}