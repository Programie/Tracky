<?php
namespace tracky\controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use tracky\datetime\DateTime;
use tracky\model\BaseEntity;
use tracky\model\User;
use tracky\model\UserCollection;
use tracky\model\UserCollectionItem;
use tracky\orm\AbstractRepository;
use tracky\orm\EpisodeRepository;
use tracky\orm\MovieRepository;
use tracky\orm\MovieSetRepository;
use tracky\orm\SeasonRepository;
use tracky\orm\ShowRepository;
use tracky\orm\UserCollectionItemRepository;
use tracky\orm\UserCollectionRepository;
use tracky\UserCollectionItemType;

class UserCollectionController extends AbstractController
{
    /**
     * @var array<string, AbstractRepository>
     */
    private array $typeToRepositoryMap;

    public function __construct(
        ShowRepository     $showRepository,
        SeasonRepository   $seasonRepository,
        EpisodeRepository  $episodeRepository,
        MovieRepository    $movieRepository,
        MovieSetRepository $movieSetRepository
    )
    {
        $this->typeToRepositoryMap = [
            UserCollectionItemType::SHOW->value => $showRepository,
            UserCollectionItemType::SEASON->value => $seasonRepository,
            UserCollectionItemType::EPISODE->value => $episodeRepository,
            UserCollectionItemType::MOVIE->value => $movieRepository,
            UserCollectionItemType::MOVIE_SET->value => $movieSetRepository
        ];
    }

    #[Route("/users/{username}/collections", name: "user_profile_collections_page", methods: ["GET"])]
    public function getListPage(User $user, UserCollectionRepository $userCollectionRepository): Response
    {
        return $this->render("user/collections/collections.twig", [
            "user" => $user,
            "collections" => $userCollectionRepository->findBy(["user" => $user])
        ]);
    }

    #[Route("/users/{username}/collections.json", name: "user_profile_collections_json", methods: ["GET"])]
    public function getCollectionsJson(User $user, UserCollectionRepository $userCollectionRepository): Response
    {
        $data = [];

        foreach ($userCollectionRepository->findBy(["user" => $user]) as $collection) {
            $data[$collection->getId()] = $collection->getName();
        }

        return $this->json($data);
    }

    #[Route("/users/{username}/collections/{collection}", name: "user_profile_collection_page", methods: ["GET"])]
    public function getCollectionPage(User $user, UserCollection $collection, Request $request): Response
    {
        $sort = explode("/", trim($request->query->get("sort", "")), 2);

        list($sortBy, $sortDirection) = $sort + ["", ""];

        $sortOptions = ["title", "type", "added", "runtime"];

        if (!in_array($sortBy, $sortOptions)) {
            $sortBy = "added";
        }

        if (!in_array($sortDirection, ["asc", "desc"])) {
            $sortDirection = "asc";
        }

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
            foreach ($this->typeToRepositoryMap[$type]->findByIds($itemIds) as $resolvedItem) {
                $index = array_search($resolvedItem->getId(), $itemIds);
                $items[$index]->setResolvedItem($resolvedItem);
            }
        }

        $items = iterator_to_array($collection->getItems());

        usort($items, function(UserCollectionItem $item1, UserCollectionItem $item2) use ($sortBy, $sortDirection) {
            $resolvedItem1 = $item1->getResolvedItem();
            $resolvedItem2 = $item2->getResolvedItem();

            switch ($sortBy) {
                case "title":
                    $value1 = $resolvedItem1->getTitle();
                    $value2 = $resolvedItem2->getTitle();
                    break;
                case "type":
                    $value1 = $item1->getType();
                    $value2 = $item2->getType();
                    break;
                case "added":
                    $value1 = $item1->getAddedAt()->getTimestamp();
                    $value2 = $item2->getAddedAt()->getTimestamp();
                    break;
                case "runtime":
                    $value1 = $resolvedItem1->getRuntime();
                    $value2 = $resolvedItem2->getRuntime();
                    break;
                default:
                    return 0;
            }

            if ($value1 < $value2) {
                return $sortDirection == "asc" ? -1 : 1;
            } elseif ($value1 > $value2) {
                return $sortDirection == "asc" ? 1 : -1;
            } else {
                return 0;
            }
        });

        return $this->render("user/collections/collection.twig", [
            "user" => $user,
            "collection" => $collection,
            "items" => $items,
            "sortOptions" => $sortOptions,
            "sort" => [
                "field" => $sortBy,
                "direction" => $sortDirection
            ]
        ]);
    }

    #[Route("/users/{username}/collections", name: "user_profile_collection_create_action", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function createCollection(User $user, Request $request, UserCollectionRepository $userCollectionRepository, EntityManagerInterface $entityManager): Response
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
            return $this->redirectToRoute("user_profile_collections_page", ["username" => $user->getUsername(), "flash" => "error", "action" => "create", "error" => "empty-name", "name" => $name]);
        }

        if ($userCollectionRepository->count(["user" => $user, "name" => $name])) {
            return $this->redirectToRoute("user_profile_collections_page", ["username" => $user->getUsername(), "flash" => "error", "action" => "create", "error" => "duplicate-collection", "name" => $name]);
        }

        $collection = new UserCollection;
        $collection->setUser($user);
        $collection->setName($name);
        $collection->setCreatedAt(new DateTime);

        $entityManager->persist($collection);
        $entityManager->flush();

        return $this->redirectToRoute("user_profile_collections_page", ["username" => $user->getUsername(), "flash" => "success", "action" => "create", "name" => $name]);
    }

    #[Route("/users/{username}/collections/{collection}", name: "user_profile_collection_remove_action", methods: ["DELETE"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function removeCollection(User $user, UserCollection $collection, EntityManagerInterface $entityManager): Response
    {
        /**
         * @var User
         */
        $currentUser = $this->getUser();

        if ($collection->getUser()->getId() !== $user->getId() or $user->getId() !== $currentUser->getId()) {
            throw new AccessDeniedHttpException;
        }

        $entityManager->remove($collection);
        $entityManager->flush();

        return new Response("Collection removed");
    }

    #[Route("/users/{username}/collections/{collection}/rename", name: "user_profile_collection_rename_action", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function renameCollection(User $user, UserCollection $collection, Request $request, UserCollectionRepository $userCollectionRepository, EntityManagerInterface $entityManager): Response
    {
        /**
         * @var User
         */
        $currentUser = $this->getUser();

        if ($collection->getUser()->getId() !== $user->getId() or $user->getId() !== $currentUser->getId()) {
            throw new AccessDeniedHttpException;
        }

        $name = trim($request->request->getString("name"));
        if ($name === "") {
            return $this->redirectToRoute("user_profile_collection_page", ["username" => $user->getUsername(), "collection" => $collection->getId(), "flash" => "error", "action" => "rename", "error" => "empty-name"]);
        }

        foreach ($userCollectionRepository->findBy(["user" => $user, "name" => $name]) as $otherCollection) {
            if ($otherCollection->getId() !== $collection->getId()) {
                return $this->redirectToRoute("user_profile_collection_page", ["username" => $user->getUsername(), "collection" => $collection->getId(), "flash" => "error", "action" => "rename", "error" => "duplicate-collection", "name" => $name]);
            }
        }

        $oldName = $collection->getName();
        $collection->setName($name);

        $entityManager->persist($collection);
        $entityManager->flush();

        return $this->redirectToRoute("user_profile_collection_page", ["username" => $user->getUsername(), "collection" => $collection->getId(), "flash" => "success", "action" => "rename", "name" => $oldName, "new-name" => $name]);
    }

    #[Route("/users/{username}/collections/{collection}/add-item", name: "user_profile_collection_add_item_action", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function addItemToCollection(User $user, UserCollection $collection, Request $request, UserCollectionItemRepository $userCollectionItemRepository, EntityManagerInterface $entityManager): Response
    {
        /**
         * @var User
         */
        $currentUser = $this->getUser();

        if ($collection->getUser()->getId() !== $user->getId() or $user->getId() !== $currentUser->getId()) {
            throw new AccessDeniedHttpException;
        }

        $payload = $request->getPayload();

        $type = UserCollectionItemType::tryFrom($payload->getString("type"));
        if ($type === null) {
            throw new BadRequestHttpException("Invalid type!");
        }

        $item = $this->typeToRepositoryMap[$type->value]->findOneBy(["id" => $payload->getInt("item")]);
        if ($item === null) {
            throw new NotFoundHttpException("Item not found!");
        }

        if ($userCollectionItemRepository->count(["collection" => $collection->getId(), "type" => $type->value, "item" => $item->getId()])) {
            throw new UnprocessableEntityHttpException("Item already exists in collection!");
        }

        $collectionItem = new UserCollectionItem;
        $collectionItem->setCollection($collection);
        $collectionItem->setItem($item);
        $collectionItem->setAddedAt(new DateTime);

        $entityManager->persist($collectionItem);
        $entityManager->flush();

        return new Response("Item added");
    }

    #[Route("/users/{username}/collections/{collection}/{collectionItem}", name: "user_profile_collection_remove_item_action", methods: ["DELETE"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function removeItemFromCollection(User $user, UserCollection $collection, UserCollectionItem $collectionItem, EntityManagerInterface $entityManager): Response
    {
        /**
         * @var User
         */
        $currentUser = $this->getUser();

        if ($collection->getUser()->getId() !== $user->getId() or $user->getId() !== $currentUser->getId() or $collection->getId() !== $collectionItem->getCollection()->getId()) {
            throw new AccessDeniedHttpException;
        }

        $entityManager->remove($collectionItem);
        $entityManager->flush();

        return new Response("Item removed");
    }
}
