<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\PlatformBridgeRenderer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class PlatformBridgeExtension extends AbstractExtension
{
    public function __construct(private PlatformBridgeRenderer $renderer) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('platform_bridge_assets', [$this->renderer, 'renderBridgeAssets'], ['is_safe' => ['html']]),
        ];
    }
}
