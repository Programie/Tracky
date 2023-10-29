<?php
namespace tracky\console;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use tracky\dataprovider\TMDB;
use tracky\model\Movie;
use tracky\model\Show;
use tracky\orm\MovieRepository;
use tracky\orm\ShowRepository;

class FetchTMDBCommand extends Command
{
    public function __construct(
        private readonly TMDB                   $tmdb,
        private readonly ShowRepository         $showRepository,
        private readonly MovieRepository        $movieRepository,
        private readonly EntityManagerInterface $entityManager
    )
    {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->setName("fetch-tmdb");
        $this->setDescription("Fetch data from The Movie Database (TMDB)");
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /**
         * @var $show Show
         */
        foreach ($this->showRepository->findAll() as $show) {
            $output->writeln(sprintf("Fetching data for show: %s", $show->getTitle()));

            $show->fetchTMDBData($this->tmdb);

            $this->entityManager->persist($show);
        }

        /**
         * @var $movie Movie
         */
        foreach ($this->movieRepository->findAll() as $movie) {
            $output->writeln(sprintf("Fetching data for movie: %s", $movie->getTitle()));

            $movie->fetchTMDBData($this->tmdb);

            $this->entityManager->persist($movie);
        }

        $this->entityManager->flush();

        $output->writeln("Done");

        return self::SUCCESS;
    }
}