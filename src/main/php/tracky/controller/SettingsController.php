<?php
namespace tracky\controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use tracky\model\UserSetting;

class SettingsController extends AbstractController
{
    public function __construct(
        private readonly int $minPasswordLength
    )
    {
    }

    #[Route("/settings", name: "settings_page")]
    #[IsGranted("IS_AUTHENTICATED")]
    public function getSettingsPage(): Response
    {
        return $this->render("user/settings/settings.twig", [
            "groupedSettings" => $this->getUser()->getSettings()->getOptionsGroupedBySections()
        ]);
    }

    #[Route("/settings/update", name: "settings_save_action", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function saveSettings(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $settingsToPersist = [];
        $settingsToRemove = [];

        foreach ($user->getSettings()->getOptions() as $option) {
            if (!$option->isSavable()) {
                continue;
            }

            if (!$option->isValid($request->request)) {
                return $this->redirectToRoute("settings_page", ["flash" => "error", "error" => "save-settings", "option" => $option->getName()]);
            }

            $value = $option->getSettingValueFromInputBag($request->request);
            if ($value === null) {
                return $this->redirectToRoute("settings_page", ["flash" => "error", "error" => "save-settings", "option" => $option->getName()]);
            }

            $setting = $option->getSetting();

            if ($option->isDefault($request->request)) {
                if ($setting !== null) {
                    $settingsToRemove[] = $setting;
                }
            } else {
                if ($setting === null) {
                    $setting = new UserSetting;
                    $setting->setUser($user);
                    $setting->setName($option->getName());
                }

                $setting->setValue($value);
                $settingsToPersist[] = $setting;
            }
        }

        foreach ($settingsToPersist as $setting) {
            $entityManager->persist($setting);
        }

        foreach ($settingsToRemove as $setting) {
            $entityManager->remove($setting);
        }

        $entityManager->flush();

        return $this->redirectToRoute("settings_page", ["flash" => "success", "action" => "save-settings"]);
    }

    #[Route("/settings/change-password", name: "settings_change_password_action", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function changePassword(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();

        if (!$passwordHasher->isPasswordValid($user, $request->request->getString("current-password"))) {
            return $this->redirectToRoute("settings_page", ["flash" => "error", "error" => "invalid-current-password"]);
        }

        $newPassword = $request->request->getString("new-password");
        $newPasswordConfirm = $request->request->getString("new-password-confirm");

        if (trim($newPassword) === "") {
            return $this->redirectToRoute("settings_page", ["flash" => "error", "error" => "new-password-missing"]);
        }

        if ($newPassword !== $newPasswordConfirm) {
            return $this->redirectToRoute("settings_page", ["flash" => "error", "error" => "new-passwords-do-not-match"]);
        }

        if (mb_strlen($newPassword) < $this->minPasswordLength) {
            return $this->redirectToRoute("settings_page", ["flash" => "error", "error" => "new-password-too-short"]);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));

        $entityManager->flush();

        return $this->redirectToRoute("settings_page", ["flash" => "success", "action" => "update-password"]);
    }
}
