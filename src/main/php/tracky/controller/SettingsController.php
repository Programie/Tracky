<?php
namespace tracky\controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use tracky\model\User;
use tracky\orm\Settings;

class SettingsController extends AbstractController
{
    private array $settingsDefinitions = [
        "overview" => [
            "type" => "section",
            "label" => "settings.section.overview.label",
        ],
        "overviewMaxEpisodes" => [
            "label"   => "settings.overview.max-episodes.label",
            "type"    => "number",
            "default" => 8,
            "min"     => 4,
            "max"     => 16,
        ],
        "overviewMaxMovies" => [
            "label"   => "settings.overview.max-movies.label",
            "type"    => "number",
            "default" => 8,
            "min"     => 4,
            "max"     => 16,
        ],
        "overviewMaxNextEpisodeShows" => [
            "label"   => "settings.overview.max-next-episode-shows.label",
            "type"    => "number",
            "default" => 8,
            "min"     => 4,
            "max"     => 16,
        ],
        "shows" => [
            "type" => "section",
            "label" => "settings.section.shows.label",
        ],
        "hideShows" => [
            "label"   => "settings.shows.hide-shows.label",
            "type"    => "checkbox",
            "options" => [
                "ended"    => "settings.shows.hide-shows.option.ended",
                "finished" => "settings.shows.hide-shows.option.finished",
            ],
            "default" => "",
        ],
        /*
        "exampleSelect" => [
            "label"   => "settings.example-select.label",
            "type"    => "select",
            "options" => ["foo" => "Foo", "bar" => "Bar", "baz" => "Baz"],
            "default" => "foo",
        ],
        "exampleText" => [
            "label"       => "settings.example-text.label",
            "type"        => "text",
            "placeholder" => "settings.example-text.placeholder",
            "regex"       => "^[a-zA-Z0-9\-_]{3,20}$",
            "default"     => "anonymous",
        ],
        "exampleRadio" => [
            "label"       => "settings.example-radio.label",
            "type"        => "radio",
            "options"     => [
                "foo" => "Foo",
                "bar" => "Bar"
            ],
            "default"     => "foo",
        ],
        */
    ];

    public function getSettingDefaults(): array
    {
        return $this->settingsDefinitions;
    }

    #[Route("/settings", name: "settings_page")]
    public function getSettingsPage(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $currentValues = [];

        if ($user) {
            $savedSettings = $entityManager->getRepository(Settings::class)->findBy(["user" => $user]);
            foreach ($savedSettings as $setting) {
                $currentValues[$setting->getSetting()] = $setting->getValue();
            }
        }

        $viewSettings = [];
        foreach ($this->settingsDefinitions as $key => $def) {
            if ($def["type"] === "section") {
                $value = null;
            } else {
                $value = $currentValues[$key] ?? $def["default"];
            }

            $viewSettings[] = [
                "key"        => $key,
                "label"      => $def["label"],
                "type"       => $def["type"],
                "options"    => $def["options"] ?? [],
                "value"      => $value,
                "placeholder"=> $def["placeholder"] ?? null,
                "min"=> $def["min"] ?? 0,
                "max"=> $def["max"] ?? 999,
            ];
        }

        return $this->render("settings.twig", [
            "settings" => $viewSettings,
        ]);
    }

    #[Route("/settings/update", name: "settings_save_action", methods: ["POST"])]
    public function saveSettings(Request $request, EntityManagerInterface $entityManager): Response
    {
        $hasError = false;
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute("login_page");
        }

        foreach ($this->settingsDefinitions as $key => $def) {
            $finalValue = "";

            if ($def["type"] === "section") {
                continue;
            }

            if ($def["type"] === "checkbox") {
                $inputValues = $request->request->all()[$key] ?? [];

                foreach ($inputValues as $val) {
                    if (!array_key_exists($val, $def["options"] ?? [])) {
                        $hasError = true;
                        break;
                    }
                }
                $finalValue = implode(",", $inputValues);
            } else {
                $inputValue = trim($request->request->getString($key, ""));
                $finalValue = $inputValue !== "" ? $inputValue : $def["default"];

                if ($def["type"] === "select") {
                    if (!array_key_exists($finalValue, $def["options"] ?? [])) {
                        $hasError = true;
                        continue;
                    }
                } elseif ($def["type"] === "radio") {
                    if (!in_array($finalValue, array_keys($def["options"] ?? []), true)) {
                        $hasError = true;
                        continue;
                    }
                } elseif ($def["type"] === "text") {
                    if (isset($def["regex"]) && !preg_match("/" . $def["regex"] . "/", $finalValue)) {
                        $hasError = true;
                        continue;
                    }
                } elseif ($def["type"] === "number") {
                    $numVal = (int)$finalValue;
                    if ((string)$numVal !== $finalValue || $numVal < ($def["min"] ?? 0) || $numVal > ($def["max"] ?? PHP_INT_MAX)) {
                        $hasError = true;
                        continue;
                    }
                }
            }

            $settingsEntity = $entityManager->getRepository(Settings::class)->findOneBy(["user" => $user, "setting" => $key]);

            if (!$settingsEntity) {
                $settingsEntity = new Settings();
                $settingsEntity->setUser($user);
                $settingsEntity->setSetting($key);
            }

            $settingsEntity->setValue($finalValue);
            $entityManager->persist($settingsEntity);

            if ($hasError) {
                return $this->redirectToRoute("settings_page", ["flash" => "error"]);
            }
        }

        $entityManager->flush();
        return $this->redirectToRoute("settings_page", ["flash" => "success"]);
    }
}
