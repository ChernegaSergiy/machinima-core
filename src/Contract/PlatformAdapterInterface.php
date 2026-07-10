<?php

declare(strict_types=1);

namespace App\Contract;

use Symfony\Component\HttpFoundation\Request;

/**
 * Marker interface for platform adapters.
 *
 * A platform adapter has three responsibilities, and NOTHING else reaches
 * into the core about a specific platform:
 *
 *   1. Identify itself (`getPlatformName`) — this is a UI/notification label,
 *      not an auth concern. Auth linking uses IdentityAssertion::providerName,
 *      which may or may not be the same string (see machinima-telegram-adapter
 *      for an example where it deliberately isn't, to avoid clashing with an
 *      unrelated IdentityProviderPort registered under the same platform).
 *   2. Declare a client-side "bootstrap" ES module — a self-contained script
 *      that self-detects the platform in the browser and, if present,
 *      resolves an opaque assertion string. The core never inspects the
 *      assertion; it is forwarded as-is to IdentityProviderPort::validateAssertion().
 *   3. Optionally declare a "UI hints" ES module — presentational-only concerns
 *      (theme, back-button, etc) for a session that is *already* authenticated
 *      via this adapter. Never involved in login.
 *
 * There is deliberately no `supports(Request)`/`getContext(Request)` pair here
 * anymore. The previous design asked adapters to sniff headers/cookies/query
 * params on every request to guess the active platform — that guesswork is
 * exactly what caused header-name drift and the bootstrap reload loop. Now:
 * which adapter is "active" for a session is a plain fact stored in the
 * session itself the moment `/api/auth/bootstrap` succeeds (see
 * AuthBootstrapController), and getUiContext() is only ever called for that
 * one, already-known adapter — no request-sniffing required.
 */
interface PlatformAdapterInterface
{
    /**
     * Stable identifier for this adapter (e.g. 'telegram'). Used to look the
     * adapter up by name once a session is known to belong to it — never
     * derived from request sniffing.
     */
    public function getPlatformName(): string;

    /**
     * Returns a PlatformUiContext for a session already known to belong to
     * this adapter. $request is provided only so an adapter may read its own
     * *cosmetic*, non-auth cookies it previously set itself (e.g. a persisted
     * theme preference) — never for platform detection or credentials.
     */
    public function getUiContext(Request $request): PlatformUiContext;

    /**
     * Returns the public (asset-relative) path to an ES module that exports:
     *
     *   export async function detect(): Promise<{ provider: string, assertion: string } | null>
     *
     * `provider` is the IdentityProviderPort registry name this assertion
     * should be validated against (chosen by the adapter itself — it need
     * not equal getPlatformName(), see machinima-telegram-adapter for why),
     * `assertion` is an opaque string core never inspects. Returns null if
     * this platform isn't detected. Or null for the whole method if this
     * adapter has no zero-click bootstrap flow at all.
     *
     * Rendered for every registered adapter on every unauthenticated page —
     * the server has no way yet to know which platform the browser is
     * running in, so every candidate self-detects client-side.
     */
    public function getBootstrapModulePath(): ?string;

    /**
     * Returns the public (asset-relative) path to an ES module that exports
     * `export function apply(ctx): Promise<void> | void`, or null. Loaded
     * once, only for the adapter that owns the current authenticated session.
     */
    public function getUiHintsModulePath(): ?string;
}
