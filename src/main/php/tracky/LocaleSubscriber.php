<?php
namespace tracky;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;
use tracky\model\User;
use tracky\settings\UserSettings;

class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly TranslatorInterface $translator
    )
    {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        /**
         * @var User
         */
        $user = $this->security->getUser();

        $settings = $user?->getSettings() ?? new UserSettings;

        $language = $settings->getOptionValue("language");
        if ($language === null or $language === "auto") {
            $language = $request->getPreferredLanguage();
        }

        $request->setLocale($language);
        $this->translator->setLocale($language);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // must be registered before (i.e. with a higher priority than) the default Locale listener
            KernelEvents::REQUEST => [["onKernelRequest", 7]],
        ];
    }
}
