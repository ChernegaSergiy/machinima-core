<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Event;

use Morfeditorial\MachinimaCoreBundle\Entity\Content;
use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

final class ContentInteractionEvent extends Event
{
    public const TYPE_LIKE = 'like';
    public const TYPE_DISLIKE = 'dislike';

    public function __construct(
        private readonly Content $content,
        private readonly User $user,
        private readonly string $type,
    ) {
    }

    public function getContent(): Content
    {
        return $this->content;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isLike(): bool
    {
        return $this->type === self::TYPE_LIKE;
    }

    public function isDislike(): bool
    {
        return $this->type === self::TYPE_DISLIKE;
    }
}
