<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Message;

final class CreateNotificationMessage
{
    public function __construct(
        private readonly int $userId,
        private readonly string $type,
        private readonly int $targetId,
        private readonly string $targetType,
        private readonly string $message,
    ) {
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTargetId(): int
    {
        return $this->targetId;
    }

    public function getTargetType(): string
    {
        return $this->targetType;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
