<?php

declare(strict_types=1);

namespace App\Contract;

/**
 * Marker interface for platform-specific splash screens.
 * 
 * Splash screens are purely visual and rely entirely on client-side JS
 * to be displayed (e.g., inside an embedded webview). The server registers
 * them as hidden <template> tags in the initial HTML payload, ensuring zero 
 * Flash of Unstyled Content (FOUC) when the client JS reveals them.
 */
interface SplashScreenInterface
{
    /**
     * Stable identifier for this splash screen's platform (e.g. 'telegram').
     * Used as a key in the DOM to identify the template.
     */
    public function getPlatformName(): string;

    /**
     * Returns the Twig template path containing the splash screen HTML.
     * e.g., '@MorfBranding/splash.html.twig'
     */
    public function getTemplatePath(): string;
}
