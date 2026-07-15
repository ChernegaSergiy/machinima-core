<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Event;

use Morfeditorial\MachinimaCoreBundle\Entity\Author;
use Symfony\Contracts\EventDispatcher\Event;

final class AuthorVisibilityChangedEvent extends Event
{
    public function __construct(
        private readonly Author $author,
        private readonly string $oldState,
        private readonly string $newState,
    ) {
    }

    public function getAuthor(): Author
    {
        return $this->author;
    }

    public function getOldState(): string
    {
        return $this->oldState;
    }

    public function getNewState(): string
    {
        return $this->newState;
    }

    public function isNowPublic(): bool
    {
        return $this->newState === 'public';
    }

    public function isNowPrivate(): bool
    {
        return $this->newState === 'private';
    }
}
