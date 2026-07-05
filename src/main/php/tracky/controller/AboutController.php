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
        $versionInfo = [];
        $versionFile = sprintf("%s/version", $this->getParameter("kernel.project_dir"));

        if (is_file($versionFile)) {
            foreach (explode("\n", file_get_contents($versionFile)) as $line) {
                $line = trim($line);
                if ($line === "") {
                    continue;
                }

                list($key, $value) = explode("=", $line, 2);

                $key = trim($key);
                $value = trim($value);

                if ($key === "") {
                    continue;
                }

                $versionInfo[$key] = $value;
            }
        }

        return $this->render("about.twig", [
            "versionInfo" => $versionInfo
        ]);
    }
}
