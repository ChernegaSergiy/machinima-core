<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Event;

use Morfeditorial\MachinimaCoreBundle\Entity\Author;
use Symfony\Contracts\EventDispatcher\Event;

final class AuthorDeletedEvent extends Event
{
    public function __construct(
        private readonly Author $author,
    ) {
    }

    public function getAuthor(): Author
    {
        return $this->author;
    }
}
