<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Event;

use Morfeditorial\MachinimaCoreBundle\Entity\Role;
use Symfony\Contracts\EventDispatcher\Event;

final class RoleDeletedEvent extends Event
{
    public function __construct(
        private readonly Role $role,
    ) {
    }

    public function getRole(): Role
    {
        return $this->role;
    }
}
