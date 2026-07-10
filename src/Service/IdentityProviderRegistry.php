<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\BootstrapOnlyIdentityProvider;
use App\Contract\IdentityProviderMetadataProvider;
use App\Contract\IdentityProviderPort;

final class IdentityProviderRegistry
{
    /** @var IdentityProviderPort[] */
    private array $providers;

    /**
     * @param iterable<IdentityProviderPort> $providers
     */
    public function __construct(
        iterable $providers,
    ) {
        $this->providers = iterator_to_array($providers);
    }

    /**
     * @return array<int, array{name: string, label: string, icon: string, login_url?: string, description?: string}>
     */
    public function getAvailableProviders(): array
    {
        $result = [];

        foreach ($this->providers as $provider) {
            if ($provider instanceof BootstrapOnlyIdentityProvider) {
                continue;
            }

            $name = $provider->getProviderName();

            if ($provider instanceof IdentityProviderMetadataProvider) {
                $metadata = $provider->getMetadata();
            } else {
                $metadata = [
                    'label' => ucfirst($name),
                    'icon' => $name,
                ];
            }

            $result[] = array_merge(['name' => $name], $metadata);
        }

        return $result;
    }

    public function getProvider(string $name): IdentityProviderPort
    {
        foreach ($this->providers as $provider) {
            if ($provider->getProviderName() === $name) {
                return $provider;
            }
        }

        throw new \InvalidArgumentException(sprintf('Identity provider "%s" not found.', $name));
    }

    public function hasProvider(string $name): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->getProviderName() === $name) {
                return true;
            }
        }

        return false;
    }
}
