<?php
namespace tracky\controller;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use tracky\model\User;
use tracky\orm\ShowRepository;
use tracky\orm\ViewRepository;
use tracky\orm\UserRepository;
use tracky\scrobbler\Scrobbler;
use tracky\ViewType;
use tracky\watchstats\WatchStatsProvider;

class UserController extends AbstractController
{
    public function __construct(
        private readonly bool $enableRegister
    )
    {
    }

    #[Route("/login", name: "login_page")]
    public function getLoginPage(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render("user/login.twig", [
            "username" => $authenticationUtils->getLastUsername(),
            "error" => $authenticationUtils->getLastAuthenticationError(),
            "registerEnabled" => $this->enableRegister
        ]);
    }

    #[Route("/logout", name: "logout")]
    public function getLogoutPage(): never
    {
        throw new Exception("Logout not enabled");
    }

    #[Route("/register", name: "register_page")]
    public function getRegisterPage(Request $request, UserPasswordHasherInterface $passwordHasher, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        if (!$this->enableRegister) {
            return $this->redirectToRoute("login_page");
        }

        if ($request->isMethod("POST")) {
            $payload = $request->getPayload();
            $username = trim($payload->getString("username"));
            $password = $payload->getString("password");
            $passwordRepeat = $payload->getString("password-repeat");

            if ($username === "") {
                return $this->render("user/register.twig", [
                    "username" => $username,
                    "error" => [
                        "messageKey" => "register.error.username-missing"
                    ]
                ]);
            }

            if ($userRepository->count(["username" => $username])) {
                return $this->render("user/register.twig", [
                    "username" => $username,
                    "error" => [
                        "messageKey" => "register.error.username-taken"
                    ]
                ]);
            }

            if (trim($password) === "") {
                return $this->render("user/register.twig", [
                    "username" => $username,
                    "error" => [
                        "messageKey" => "register.error.password-missing"
                    ]
                ]);
            }

            if ($password !== $passwordRepeat) {
                return $this->render("user/register.twig", [
                    "username" => $username,
                    "error" => [
                        "messageKey" => "register.error.passwords-do-not-match"
                    ]
                ]);
            }

            $user = new User;
            $user->setUsername($username);

            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute("login_page", ["username" => $username]);
        }

        return $this->render("user/register.twig", [
            "username" => null,
            "error" => null
        ]);
    }

    #[Route("/users/{username}", name: "user_profile_page")]
    public function getProfilePage(User $user, ViewRepository $viewRepository, Scrobbler $scrobbler): Response
    {
        return $this->render("user/profile.twig", [
            "user" => $user,
            "nowWatching" => $scrobbler->getNowWatching($user),
            "latestWatchedEpisodes" => $viewRepository->findBy(["user" => $user->getId()], ["dateTime" => "desc"], 10, type: ViewType::EPISODE),
            "latestWatchedMovies" => $viewRepository->findBy(["user" => $user->getId()], ["dateTime" => "desc"], 10, type: ViewType::MOVIE)
        ]);
    }

    #[Route("/users/{username}/show-progress", name: "user_profile_show_progress_page")]
    public function getShowProgressForUser(User $user, ShowRepository $showRepository, WatchStatsProvider $watchStatsProvider): Response
    {
        return $this->render("user/show-progress.twig", [
            "user" => $user,
            "shows" => $showRepository->findAllWithEpisodes(),
            "watchStatsCollection" => $watchStatsProvider->getStatsForType(ViewType::EPISODE, $user)
        ]);
    }
}
