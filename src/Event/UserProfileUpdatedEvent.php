<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Event;

use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

final class UserProfileUpdatedEvent extends Event
{
    public function __construct(
        private readonly User $user,
        private readonly array $changedFields,
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getChangedFields(): array
    {
        return $this->changedFields;
    }

    public function hasChanged(string $field): bool
    {
        return in_array($field, $this->changedFields, true);
    }
}
