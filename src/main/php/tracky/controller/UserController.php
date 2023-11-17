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
            $payload = $request->getPayload();
            $username = trim($payload->getString("username"));
            $password = $payload->getString("password");
            $passwordRepeat = $payload->getString("password-repeat");

            if ($username === "") {
                return $this->render("user/register.twig", [
                    "username" => $username,
                    "error" => [
                        "message" => "No username given"
                    ]
                ]);
            }

            if ($userRepository->count(["username" => $username])) {
                return $this->render("user/register.twig", [
                    "username" => $username,
                    "error" => [
                        "message" => "The username is already taken"
                    ]
                ]);
            }

            if (trim($password) === "") {
                return $this->render("user/register.twig", [
                    "username" => $username,
                    "error" => [
                        "message" => "No password given"
                    ]
                ]);
            }

            if ($password !== $passwordRepeat) {
                return $this->render("user/register.twig", [
                    "username" => $username,
                    "error" => [
                        "message" => "Passwords are not the same"
                    ]
                ]);
            }

            $user = new User;
            $user->setUsername($username);

            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute("loginPage", ["username" => $username]);
        }

        return $this->render("user/register.twig", [
            "username" => null,
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