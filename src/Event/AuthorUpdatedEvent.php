<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Event;

use Morfeditorial\MachinimaCoreBundle\Entity\Author;
use Symfony\Contracts\EventDispatcher\Event;

final class AuthorUpdatedEvent extends Event
{
    public function __construct(
        private readonly Author $author,
        private readonly array $changedFields,
    ) {
    }

    public function getAuthor(): Author
    {
        return $this->author;
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
