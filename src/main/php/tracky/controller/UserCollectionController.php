<?php
namespace tracky\controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use tracky\datetime\DateTime;
use tracky\model\BaseEntity;
use tracky\model\User;
use tracky\model\UserCollection;
use tracky\model\UserCollectionItem;
use tracky\orm\EpisodeRepository;
use tracky\orm\MovieRepository;
use tracky\orm\MovieSetRepository;
use tracky\orm\SeasonRepository;
use tracky\orm\ShowRepository;
use tracky\orm\UserCollectionRepository;
use tracky\UserCollectionItemType;

class UserCollectionController extends AbstractController
{
    #[Route("/users/{username}/collections", name: "user_profile_collections_page", methods: ["GET"])]
    public function getListPage(User $user, UserCollectionRepository $userCollectionRepository): Response
    {
        return $this->render("user/collections/collections.twig", [
            "user" => $user,
            "collections" => $userCollectionRepository->findBy(["user" => $user])
        ]);
    }

    #[Route("/users/{username}/collections/{collection}", name: "user_profile_collection_page", methods: ["GET"])]
    public function getCollectionPage(
        User               $user,
        UserCollection     $collection,
        ShowRepository     $showRepository,
        SeasonRepository   $seasonRepository,
        EpisodeRepository  $episodeRepository,
        MovieRepository    $movieRepository,
        MovieSetRepository $movieSetRepository
    ): Response
    {
        $typeToRepositoryMap = [
            UserCollectionItemType::SHOW->value => $showRepository,
            UserCollectionItemType::SEASON->value => $seasonRepository,
            UserCollectionItemType::EPISODE->value => $episodeRepository,
            UserCollectionItemType::MOVIE->value => $movieRepository,
            UserCollectionItemType::MOVIE_SET->value => $movieSetRepository
        ];

        /**
         * @var array<string, array<int, UserCollectionItem>>
         */
        $perTypeItems = [];

        foreach ($collection->getItems() as $item) {
            $type = $item->getType()->value;

            if (!isset($perTypeItems[$type])) {
                $perTypeItems[$type] = [];
            }

            $perTypeItems[$type][$item->getId()] = $item;
        }

        foreach ($perTypeItems as $type => $items) {
            $itemIds = array_map(fn($item) => $item->getItem(), $items);

            /**
             * @var BaseEntity $resolvedItem
             */
            foreach ($typeToRepositoryMap[$type]->findByIds($itemIds) as $resolvedItem) {
                $index = array_search($resolvedItem->getId(), $itemIds);
                $items[$index]->setResolvedItem($resolvedItem);
            }
        }

        return $this->render("user/collections/collection.twig", [
            "user" => $user,
            "collection" => $collection
        ]);
    }

    #[Route("/users/{username}/collections", name: "user_profile_collection_create_action", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function createCollection(User $user, Request $request, UserCollectionRepository $userCollectionRepository, EntityManagerInterface $entityManagerInterface): Response
    {
        /**
         * @var User
         */
        $currentUser = $this->getUser();

        if ($user->getId() !== $currentUser->getId()) {
            throw new AccessDeniedHttpException;
        }

        $name = trim($request->request->getString("name"));

        if ($name === "") {
            return $this->redirectToRoute("user_profile_collections_page", ["username" => $user->getUsername(), "flash" => "error", "error" => "empty-name", "name" => $name]);
        }

        if ($userCollectionRepository->count(["user" => $user, "name" => $name])) {
            return $this->redirectToRoute("user_profile_collections_page", ["username" => $user->getUsername(), "flash" => "error", "error" => "duplicate-collection", "name" => $name]);
        }

        $collection = new UserCollection;
        $collection->setUser($user);
        $collection->setName($name);
        $collection->setCreatedAt(new DateTime);

        $entityManagerInterface->persist($collection);
        $entityManagerInterface->flush();

        return $this->redirectToRoute("user_profile_collections_page", ["username" => $user->getUsername(), "flash" => "success", "name" => $name]);
    }
}
