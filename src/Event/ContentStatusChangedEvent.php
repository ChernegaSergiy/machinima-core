<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Event;

use Morfeditorial\MachinimaCoreBundle\Entity\Content;
use Symfony\Contracts\EventDispatcher\Event;

final class ContentStatusChangedEvent extends Event
{
    public function __construct(
        private readonly Content $content,
        private readonly string $oldStatus,
        private readonly string $newStatus,
    ) {
    }

    public function getContent(): Content
    {
        return $this->content;
    }

    public function getOldStatus(): string
    {
        return $this->oldStatus;
    }

    public function getNewStatus(): string
    {
        return $this->newStatus;
    }
}
