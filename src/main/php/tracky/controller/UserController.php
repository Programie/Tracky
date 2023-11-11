<?php
namespace tracky\controller;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use tracky\model\User;
use tracky\orm\ShowRepository;
use tracky\orm\ViewRepository;
use tracky\orm\UserRepository;

class UserController extends AbstractController
{
    #[Route("/login", name: "loginPage")]
    public function getLoginPage(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render("user/login.twig", [
            "username" => $authenticationUtils->getLastUsername(),
            "error" => $authenticationUtils->getLastAuthenticationError()
        ]);
    }

    #[Route("/logout", name: "logout")]
    public function getLogoutPage(): never
    {
        throw new Exception("Logout not enabled");
    }

    #[Route("/register", name: "registerPage")]
    public function getRegisterPage(Request $request, UserPasswordHasherInterface $passwordHasher, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod("POST")) {
            $username = $request->getPayload()->getString("username");
            $password = $request->getPayload()->getString("password");

            if ($userRepository->count(["username" => $username])) {
                return $this->render("user/register.twig", [
                    "error" => [
                        "message" => "The username is already taken"
                    ]
                ]);
            }

            $user = new User;
            $user->setUsername($username);

            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute("profilePage", ["username" => $username]);
        }

        return $this->render("user/register.twig", [
            "error" => null
        ]);
    }

    #[Route("/users/{username}", name: "profilePage")]
    public function getProfilePage(User $user, ViewRepository $viewRepository): Response
    {
        return $this->render("user/profile.twig", [
            "user" => $user,
            "latestWatchedEpisodes" => $viewRepository->findBy(["user" => $user->getId()], ["dateTime" => "desc"], 10, type: "episode"),
            "latestWatchedMovies" => $viewRepository->findBy(["user" => $user->getId()], ["dateTime" => "desc"], 10, type: "movie")
        ]);
    }

    #[Route("/users/{username}/show-progress", name: "userShowProgressPage")]
    public function getShowProgressForUser(User $user, ShowRepository $showRepository): Response
    {
        return $this->render("user/show-progress.twig", [
            "user" => $user,
            "shows" => $showRepository->findAll()
        ]);
    }
}