<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Contract;

use Symfony\Component\HttpFoundation\Request;

interface ClientAssertionExtractorPort
{
    public function extractAssertion(Request $request): ?IdentityAssertion;
}
