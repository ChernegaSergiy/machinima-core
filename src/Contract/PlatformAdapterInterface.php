<?php

declare(strict_types=1);

namespace App\Contract;

use Symfony\Component\HttpFoundation\Request;

/**
 * Marker interface for platform adapters.
 *
 * A platform adapter has exactly two responsibilities:
 *   1. Detect whether the current request originates from its platform
 *      (`supports`) and describe it for the UI layer (`getContext`).
 *   2. Declare a client-side "bridge" template — JS/markup that self-detects
 *      the platform in the browser and bootstraps it (theming, zero-click
 *      session bootstrap, etc).
 *
 * Actual authentication is deliberately NOT part of this contract. It is a
 * Symfony Security concern, wired via a security authenticator factory
 * (see telegram-bot-bundle's `telegram_tma` firewall authenticator). Two
 * previous methods, `getZeroClickLoginUrl()` and `getLoginRouteName()`,
 * used to live here but were never called by anything — they were a
 * half-finished idea that the bridge template + authenticator approach
 * below has now fully superseded. Removed to keep the contract honest
 * about what the core actually uses.
 */
interface PlatformAdapterInterface
{
    /**
     * Returns true if this adapter can handle the given request.
     */
    public function supports(Request $request): bool;

    /**
     * Returns a PlatformUiContext appropriate for the request.
     *
     * @throws \LogicException if the adapter does not support the request
     */
    public function getContext(Request $request): PlatformUiContext;

    /**
     * Returns the Twig template path for the platform's bridge assets
     * (scripts/styles), or null if the adapter does not need to inject
     * client-side code.
     *
     * IMPORTANT: this template is rendered UNCONDITIONALLY on every page,
     * for every registered adapter — not just the one that `supports()`
     * the current request. This is intentional: on the very first request
     * from inside e.g. a Telegram Mini App, the server has no way to know
     * that yet (Telegram passes initData via a URL fragment, which never
     * reaches the server). The bridge script itself is responsible for
     * self-detecting its platform client-side and no-op'ing otherwise.
     */
    public function getBridgeTemplatePath(): ?string;
}
