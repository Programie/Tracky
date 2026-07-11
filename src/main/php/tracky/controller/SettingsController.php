<?php
namespace tracky\controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use tracky\model\User;
use tracky\model\UserSetting;

class SettingsController extends AbstractController
{
    #[Route("/settings", name: "settings_page")]
    #[IsGranted("IS_AUTHENTICATED")]
    public function getSettingsPage(): Response
    {
        /**
         * @var User
         */
        $user = $this->getUser();

        return $this->render("user/settings/settings.twig", [
            "settings" => $user->getSettings()->getOptions()
        ]);
    }

    #[Route("/settings/update", name: "settings_save_action", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function saveSettings(Request $request, EntityManagerInterface $entityManager): Response
    {
        /**
         * @var User
         */
        $user = $this->getUser();
        $settingsToPersist = [];

        foreach ($user->getSettings()->getOptions() as $option) {
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
                $setting = new UserSetting;
                $setting->setUser($user);
                $setting->setName($option->getName());
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
