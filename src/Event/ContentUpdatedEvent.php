<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Event;

use Morfeditorial\MachinimaCoreBundle\Entity\Content;
use Symfony\Contracts\EventDispatcher\Event;

final class ContentUpdatedEvent extends Event
{
    public function __construct(
        private readonly Content $content,
        private readonly array $changedFields,
    ) {
    }

    public function getContent(): Content
    {
        return $this->content;
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
