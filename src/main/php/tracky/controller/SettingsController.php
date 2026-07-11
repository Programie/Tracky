<?php
namespace tracky\controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use tracky\model\Setting;
use tracky\orm\SettingRepository;
use tracky\settings\UserSettings;

class SettingsController extends AbstractController
{
    public function __construct(
        private readonly SettingRepository $settingRepository
    )
    {
    }

    #[Route("/settings", name: "settings_page")]
    #[IsGranted("IS_AUTHENTICATED")]
    public function getSettingsPage(): Response
    {
        return $this->render("user/settings/settings.twig", [
            "settings" => (new UserSettings($this->settingRepository, $this->getUser()))->getOptions()
        ]);
    }

    #[Route("/settings/update", name: "settings_save_action", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function saveSettings(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $userSettings = new UserSettings($this->settingRepository, $user);
        $settingsToPersist = [];

        foreach ($userSettings->getOptions() as $option) {
            if (!$option->isSavable()) {
                continue;
            }

            if (!$option->isValid($request->request)) {
                return $this->redirectToRoute("settings_page", ["flash" => "error", "error" => "invalid", "option" => $option->getName()]);
            }

            $value = $option->getSettingValueFromInputBag($request->request);
            if ($value === null) {
                return $this->redirectToRoute("settings_page", ["flash" => "error", "error" => "null", "option" => $option->getName()]);
            }

            $setting = $option->getSetting();
            if ($setting === null) {
                $setting = new Setting;
                $setting->setUser($user);
                $setting->setSetting($option->getName());
            }

            $setting->setValue($value);
            $settingsToPersist[] = $setting;
        }

        foreach ($settingsToPersist as $setting) {
            $entityManager->persist($setting);
        }
        $entityManager->flush();

        return $this->redirectToRoute("settings_page", ["flash" => "success"]);
    }
}
