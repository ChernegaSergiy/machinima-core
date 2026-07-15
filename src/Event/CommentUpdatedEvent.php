<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Event;

use Morfeditorial\MachinimaCoreBundle\Entity\Comment;
use Symfony\Contracts\EventDispatcher\Event;

final class CommentUpdatedEvent extends Event
{
    public function __construct(
        private readonly Comment $comment,
        private readonly string $oldText,
        private readonly string $newText,
    ) {
    }

    public function getComment(): Comment
    {
        return $this->comment;
    }

    public function getOldText(): string
    {
        return $this->oldText;
    }

    public function getNewText(): string
    {
        return $this->newText;
    }
}
