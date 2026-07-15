<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Event;

use Morfeditorial\MachinimaCoreBundle\Entity\Content;
use Symfony\Contracts\EventDispatcher\Event;

final class ContentDeletedEvent extends Event
{
    public function __construct(
        private readonly Content $content,
    ) {
    }

    public function getContent(): Content
    {
        return $this->content;
    }
}
