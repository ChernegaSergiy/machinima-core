<?php

declare(strict_types=1);

namespace App\Contract;

interface IdentityProviderPort
{
    public function getProviderName(): string;

    public function validateAssertion(string $rawAssertion): IdentityAssertion;
}
