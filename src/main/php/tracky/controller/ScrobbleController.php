<?php
namespace tracky\controller;

use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use tracky\datetime\DateTime;
use tracky\scrobbler\Scrobbler;
use UnexpectedValueException;

class ScrobbleController extends AbstractController
{
    #[Route("/api/scrobble", name: "scrobble", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function scrobble(Request $request, Scrobbler $scrobbler): Response
    {
        try {
            $json = $request->toArray();
        } catch (Exception) {
            return new Response("Invalid JSON in request body", 400);
        }

        $event = $json["event"] ?? "end";

        if ($event !== "end") {
            return $this->returnPlainText(sprintf("Event is '%s', only accepting 'end'", $event));
        }

        try {
            if (isset($json["timestamp"])) {
                $timestamp = new DateTime($json["timestamp"]);
            } else {
                $timestamp = new DateTime;
            }
        } catch (Exception) {
            return new Response("Invalid timestamp", 400);
        }

        try {
            return $this->returnPlainText($scrobbler->cacheOrAddView($json, $timestamp, $this->getUser()));
        } catch (UnexpectedValueException $exception) {
            return new Response($exception->getMessage(), 400);
        }
    }

    private function returnPlainText(string $content): Response
    {
        return new Response($content);
    }
}