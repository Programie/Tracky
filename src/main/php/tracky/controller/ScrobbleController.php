<?php
namespace tracky\controller;

use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
        $json = $request->toArray();

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
            throw new BadRequestException("Invalid timestamp");
        }

        try {
            return $this->returnPlainText($scrobbler->cacheOrAddView($json, $timestamp, $this->getUser()));
        } catch (UnexpectedValueException $exception) {
            throw new BadRequestException($exception->getMessage());
        }
    }

    private function returnPlainText(string $content): Response
    {
        return new Response($content);
    }
}