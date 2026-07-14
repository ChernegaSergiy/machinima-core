<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Contract;

class NullIdentityProvider implements IdentityProviderPort
{
    public function getProviderName(): string
    {
        return 'null';
    }

    public function validateAssertion(string $rawAssertion): IdentityAssertion
    {
        throw new \RuntimeException('No identity provider is configured.');
    }
}
