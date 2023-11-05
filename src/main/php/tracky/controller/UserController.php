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
use tracky\orm\EpisodeViewRepository;
use tracky\orm\MovieViewRepository;
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
    public function getProfilePage(User $user, EpisodeViewRepository $episodeViewRepository, MovieViewRepository $movieViewRepository): Response
    {
        return $this->render("user/profile.twig", [
            "user" => $user,
            "latestWatchedEpisodes" => $episodeViewRepository->findBy(["user" => $user], ["dateTime" => "desc"], 10),
            "latestWatchedMovies" => $movieViewRepository->findBy(["user" => $user], ["dateTime" => "desc"], 10)
        ]);
    }
}