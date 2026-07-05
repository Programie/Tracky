<?php
namespace tracky\controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AboutController extends AbstractController
{
    #[Route("/about", name: "about_page")]
    public function about(): Response
    {
        return $this->render("about.twig", [
            "version" => $_ENV["APP_VERSION"] ?? null,
            "commit" => $_ENV["APP_GIT_COMMIT"] ?? null
        ]);
    }
}
