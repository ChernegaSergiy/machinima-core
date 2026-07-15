<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Event;

use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

final class UserStateClearedEvent extends Event
{
    public function __construct(
        private readonly User $user,
        private readonly ?string $key = null,
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function isFullClear(): bool
    {
        return $this->key === null;
    }
}
