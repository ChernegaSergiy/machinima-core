<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Controller;

use Morfeditorial\MachinimaCoreBundle\Service\IdentityProviderRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LoginController extends AbstractController
{
    public function __construct(
        private IdentityProviderRegistry $registry,
    ) {
    }

    #[Route('/login', name: 'app_login')]
    public function login(): Response
    {
        // Zero-click Telegram login authenticates the user directly on the
        // security firewall (see telegram-bot-bundle's `telegram_tma`
        // authenticator) — there is no redirect involved, the session is
        // just silently upgraded to an authenticated one on whichever
        // request happened to carry `initData`, including this very one.
        // Without this check, a successfully zero-click-authenticated user
        // would still see the "choose a login method" screen, because
        // nothing here ever looked at the auth state before rendering it.
        if ($this->getUser()) {
            return $this->redirectToRoute('app_index');
        }

        $providers = $this->registry->getAvailableProviders();

        if (0 === count($providers)) {
            $this->addFlash('error', 'No authentication providers available.');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('@MachinimaCore/login.html.twig', [
            'providers' => $providers,
        ]);
    }
}
