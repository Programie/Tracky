<?php
namespace tracky\controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use tracky\dataprovider\Helper;
use tracky\model\Movie;
use tracky\model\Show;
use tracky\orm\MovieRepository;
use tracky\orm\ShowRepository;

class AddItemsController extends AbstractController
{
    public function __construct(
        private readonly Helper          $dataProviderHelper,
        private readonly MovieRepository $movieRepository,
        private readonly ShowRepository  $showRepository
    )
    {
    }

    #[Route("/add", name: "addItemsPage", methods: ["GET"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function getPage(Request $request): Response
    {
        $type = strtolower(trim($request->query->get("type", "")));
        $query = trim($request->query->get("query", ""));
        $year = $request->query->get("year");

        if (!$year) {
            $year = null;
        }

        $results = null;
        $repository = null;

        if ($query !== "") {
            $dataProvider = $this->dataProviderHelper->getProviderByType($type);
            if ($dataProvider !== null) {
                switch ($type) {
                    case Helper::TYPE_MOVIE:
                        $results = $dataProvider->searchMovie($query, $year);
                        $repository = $this->movieRepository;
                        break;
                    case Helper::TYPE_SHOW:
                        $results = $dataProvider->searchShow($query, $year);
                        $repository = $this->showRepository;
                        break;
                }

                if ($results !== null and $repository !== null) {
                    foreach ($results as &$result) {
                        $result["entry"] = $repository->findOneBy([$dataProvider->getIdFieldName() => $result["id"]]);
                    }
                }
            }
        }

        return $this->render("add-items.twig", [
            "type" => $type,
            "query" => $query,
            "year" => $year ?: "",
            "results" => $results
        ]);
    }

    #[Route("/add", name: "addItem", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function addItem(Request $request, EntityManagerInterface $entityManager): Response
    {
        $payload = $request->getPayload();
        $id = trim($payload->get("id", ""));
        $type = strtolower(trim($payload->get("type", "")));

        $dataProvider = $this->dataProviderHelper->getProviderByType($type);

        switch ($type) {
            case Helper::TYPE_MOVIE:
                $movie = new Movie;

                $dataProvider->setIdForMovie($movie, $id);
                $dataProvider->fetchMovie($movie);

                $entityManager->persist($movie);
                $entityManager->flush();

                return $this->json(["id" => $movie->getId()]);
            case Helper::TYPE_SHOW:
                $show = new Show;

                $dataProvider->setIdForShow($show, $id);
                $dataProvider->fetchShow($show, true);

                $entityManager->persist($show);
                $entityManager->flush();

                return $this->json(["id" => $show->getId()]);
            default:
                throw new BadRequestException(sprintf("Invalid type: %s", $type));
        }
    }
}