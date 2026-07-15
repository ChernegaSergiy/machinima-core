<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Event;

use Morfeditorial\MachinimaCoreBundle\Entity\Role;
use Symfony\Contracts\EventDispatcher\Event;

final class RoleHierarchyChangedEvent extends Event
{
    public const ACTION_ADDED = 'added';
    public const ACTION_REMOVED = 'removed';

    public function __construct(
        private readonly Role $parent,
        private readonly Role $child,
        private readonly string $action,
    ) {
    }

    public function getParent(): Role
    {
        return $this->parent;
    }

    public function getChild(): Role
    {
        return $this->child;
    }

    public function getAction(): string
    {
        return $this->action;
    }
}
