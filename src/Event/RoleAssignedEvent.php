<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Event;

use Morfeditorial\MachinimaCoreBundle\Entity\Role;
use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

final class RoleAssignedEvent extends Event
{
    public function __construct(
        private readonly User $user,
        private readonly Role $role,
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRole(): Role
    {
        return $this->role;
    }
}
