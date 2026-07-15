<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Event;

use Morfeditorial\MachinimaCoreBundle\Entity\Comment;
use Morfeditorial\MachinimaCoreBundle\Entity\Content;
use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

final class CommentDeletedEvent extends Event
{
    public function __construct(
        private readonly Comment $comment,
        private readonly Content $content,
        private readonly User $user,
    ) {
    }

    public function getComment(): Comment
    {
        return $this->comment;
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
