<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Model;

class ContentItem
{
    public function __construct(
        private int $id,
        private string $status,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }
}
