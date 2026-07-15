<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Event;

use Morfeditorial\MachinimaCoreBundle\Entity\Content;
use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

final class ContentViewedEvent extends Event
{
    public function __construct(
        private readonly Content $content,
        private readonly User $user,
    ) {
    }

    public function getContent(): Content
    {
        return $this->content;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
