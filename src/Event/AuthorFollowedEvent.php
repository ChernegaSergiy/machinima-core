<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Event;

use Morfeditorial\MachinimaCoreBundle\Entity\Author;
use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

final class AuthorFollowedEvent extends Event
{
    public function __construct(
        private readonly User $follower,
        private readonly Author $author,
    ) {
    }

    public function getFollower(): User
    {
        return $this->follower;
    }

    public function getAuthor(): Author
    {
        return $this->author;
    }
}
