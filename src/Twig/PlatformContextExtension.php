<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PlatformContextExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('platform_context', [PlatformUiContextRuntime::class, 'getContext']),
            new TwigFunction('platform_bootstrap_module_paths', [PlatformUiContextRuntime::class, 'getBootstrapModulePaths']),
            new TwigFunction('platform_ui_hints_module_path', [PlatformUiContextRuntime::class, 'getUiHintsModulePath']),
            new TwigFunction('ui_splash_screens', [PlatformUiContextRuntime::class, 'getSplashScreens']),
        ];
    }
}
