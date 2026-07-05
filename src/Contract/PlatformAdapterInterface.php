<?php

declare(strict_types=1);

namespace App\Contract;

use Symfony\Component\HttpFoundation\Request;

/**
 * Marker interface for platform adapters.
 *
 * Each adapter must be able to decide whether it can handle the current
 * HTTP request (`supports`) and, if so, provide a concrete `PlatformUiContext`
 * implementation (`getContext`).
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
     * Returns the Twig template path for bridge assets (scripts/styles),
     * or null if the adapter does not need to inject client-side code.
     */
    public function getBridgeTemplatePath(): ?string;

    /**
     * Returns the URL for zero-click login, or null if not supported.
     */
    public function getZeroClickLoginUrl(): ?string;

    /**
     * Returns the login route name for this platform, or null.
     */
    public function getLoginRouteName(): ?string;
}
