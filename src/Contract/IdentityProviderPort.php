<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Contract;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.identity_provider')]
interface IdentityProviderPort
{
    public function getProviderName(): string;

    public function validateAssertion(string $rawAssertion): IdentityAssertion;
}
