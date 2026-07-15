<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Event;

use Morfeditorial\MachinimaCoreBundle\Entity\Author;
use Morfeditorial\MachinimaCoreBundle\Entity\Content;
use Symfony\Contracts\EventDispatcher\Event;

final class ContentStaffRemovedEvent extends Event
{
    public function __construct(
        private readonly Content $content,
        private readonly Author $author,
        private readonly string $role,
    ) {
    }

    public function getContent(): Content
    {
        return $this->content;
    }

    public function getAuthor(): Author
    {
        return $this->author;
    }

    public function getRole(): string
    {
        return $this->role;
    }
}
