<?php

declare(strict_types=1);

namespace App\Event;

use App\Contract\IdentityAssertion;
use Symfony\Contracts\EventDispatcher\Event;

class UserAuthenticatedEvent extends Event
{
    private ?\App\Entity\User $user = null;

    public function __construct(
        private readonly IdentityAssertion $assertion,
    ) {
    }

    public function getAssertion(): IdentityAssertion
    {
        return $this->assertion;
    }

    public function getUser(): ?\App\Entity\User
    {
        return $this->user;
    }

    public function setUser(\App\Entity\User $user): void
    {
        $this->user = $user;
    }
}
