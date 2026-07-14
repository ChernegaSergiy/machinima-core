<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Controller\Api;

use Morfeditorial\MachinimaCoreBundle\Event\UserAuthenticatedEvent;
use Morfeditorial\MachinimaCoreBundle\Service\IdentityProviderRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Single, generic entry point for zero-click platform login.
 *
 * Replaces the old per-adapter "carry initData in a custom header on every
 * request" scheme entirely. A platform's bootstrap ES module (see
 * PlatformAdapterInterface::getBootstrapModulePath()) self-detects its
 * runtime in the browser and POSTs the resulting opaque assertion here
 * exactly once. No adapter-specific header name, cookie name, or query
 * parameter is known to (or needed by) this controller, app.js, or any core
 * template — it is just {provider, assertion} in a JSON body.
 */
final class AuthBootstrapController extends AbstractController
{
    public function __construct(
        private IdentityProviderRegistry $registry,
        private EventDispatcherInterface $eventDispatcher,
        private Security $security,
    ) {
    }

    #[Route('/api/auth/bootstrap', name: 'app_auth_bootstrap', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        $providerName = $payload['provider'] ?? null;
        $assertion = $payload['assertion'] ?? null;

        if (!is_string($providerName) || !is_string($assertion) || '' === $assertion) {
            return $this->json(['error' => 'invalid_request'], 422);
        }

        if (!$this->registry->hasProvider($providerName)) {
            return $this->json(['error' => 'unsupported_provider'], 422);
        }

        try {
            $identityAssertion = $this->registry->getProvider($providerName)->validateAssertion($assertion);
        } catch (\Throwable) {
            return $this->json(['error' => 'invalid_assertion'], 401);
        }

        $event = new UserAuthenticatedEvent($identityAssertion);
        $this->eventDispatcher->dispatch($event);

        $user = $event->getUser();
        if (null === $user) {
            return $this->json(['error' => 'authentication_failed'], 401);
        }

        $this->security->login($user, 'form_login', 'main');

        // Recorded under the assertion's own providerName (the identity-
        // linking key), not the registry lookup name — a bootstrap provider
        // may validate under one registry name while asserting identity
        // under a different, platform-facing providerName (see
        // machinima-telegram-adapter's TelegramMiniAppIdentityProvider for
        // why that distinction exists).
        $request->getSession()->set('active_platform_provider', $identityAssertion->getProviderName());

        return $this->json(['ok' => true]);
    }
}
