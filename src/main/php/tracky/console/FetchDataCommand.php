<?php
namespace tracky\console;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use tracky\dataprovider\Helper;
use tracky\ImageFetcher;
use tracky\model\Movie;
use tracky\model\MovieSet;
use tracky\model\Show;
use tracky\orm\MovieRepository;
use tracky\orm\MovieSetRepository;
use tracky\orm\ShowRepository;
use UnexpectedValueException;

#[AsCommand(name: "fetch-data", description: "Fetch show or movie data from the configured data provider")]
class FetchDataCommand extends Command
{
    public function __construct(
        private readonly Helper                 $dataProviderHelper,
        private readonly MovieRepository        $movieRepository,
        private readonly MovieSetRepository     $movieSetRepository,
        private readonly ShowRepository         $showRepository,
        private readonly ImageFetcher           $imageFetcher,
        private readonly EntityManagerInterface $entityManager,
        private readonly bool                   $downloadAllImages
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument("type", InputArgument::REQUIRED, "The type (movie, movieset or show)");
        $this->addArgument("id", InputArgument::IS_ARRAY, "The ID(s) for the movie, movieset or show (omit to fetch all of that type)");
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = strtolower(trim($input->getArgument("type")));
        $ids = array_map(fn($id) => (int)$id, $input->getArgument("id"));

        switch ($type) {
            case Helper::TYPE_SHOW:
                if (empty($ids)) {
                    $shows = $this->showRepository->findAll();
                } else {
                    $shows = $this->showRepository->findByIds($ids);
                }

                /**
                 * @var Show $show
                 */
                foreach ($shows as $show) {
                    $dataProvider = $this->dataProviderHelper->getProviderByEntry($show);

                    $output->writeln(sprintf("Fetching data for show %d (%s) using provider %s", $show->getId(), $show->getTitle(), $dataProvider::class));

                    if (!$dataProvider->fetchShow($show, true)) {
                        $output->writeln(sprintf("ERROR: Fetching show %d (%s) failed!", $show->getId(), $show->getTitle()));
                        continue;
                    }

                    $this->entityManager->persist($show);
                    $this->entityManager->flush();

                    if ($this->downloadAllImages) {
                        $output->writeln(sprintf("Fetching images for show %d (%s)", $show->getId(), $show->getTitle()));

                        $show->fetchPosterImages($this->imageFetcher, true, true);
                    }
                }
                break;
            case Helper::TYPE_MOVIE:
                if (empty($ids)) {
                    $movies = $this->movieRepository->findAll();
                } else {
                    $movies = $this->movieRepository->findByIds($ids);
                }

                /**
                 * @var Movie $movie
                 */
                foreach ($movies as $movie) {
                    $dataProvider = $this->dataProviderHelper->getProviderByEntry($movie);

                    $output->writeln(sprintf("Fetching data for movie %d (%s) using provider %s", $movie->getId(), $movie->getTitle(), $dataProvider::class));

                    if (!$dataProvider->fetchMovie($movie)) {
                        $output->writeln(sprintf("ERROR: Fetching movie %d (%s) failed!", $movie->getId(), $movie->getTitle()));
                        continue;
                    }

                    $this->entityManager->persist($movie);
                    $this->entityManager->flush();

                    if ($this->downloadAllImages) {
                        $output->writeln(sprintf("Fetching images for movie %d (%s)", $movie->getId(), $movie->getTitle()));

                        $movie->fetchPosterImage($this->imageFetcher);
                    }
                }
                break;
            case Helper::TYPE_MOVIE_SET:
                if (empty($ids)) {
                    $movieSets = $this->movieSetRepository->findAll();
                } else {
                    $movieSets = $this->movieSetRepository->findByIds($ids);
                }

                /**
                 * @var MovieSet
                 */
                foreach ($movieSets as $movieSet) {
                    $dataProvider = $this->dataProviderHelper->getProviderByEntry($movieSet);

                    $output->writeln(sprintf("Fetching data for movie set %d (%s) using provider %s", $movieSet->getId(), $movieSet->getTitle(), $dataProvider::class));

                    if (!$dataProvider->fetchMovieSet($movieSet)) {
                        $output->writeln(sprintf("ERROR: Fetching movie set %d (%s) failed!", $movieSet->getId(), $movieSet->getTitle()));
                        continue;
                    }

                    $this->entityManager->persist($movieSet);
                    $this->entityManager->flush();

                    if ($this->downloadAllImages) {
                        $output->writeln(sprintf("Fetching images for movie set %d (%s)", $movieSet->getId(), $movieSet->getTitle()));

                        $movieSet->fetchPosterImages($this->imageFetcher, true);
                    }
                }
                break;
            default:
                throw new UnexpectedValueException(sprintf("Unknown type: %s", $type));
        }

        $output->writeln("Done");

        return self::SUCCESS;
    }
}
