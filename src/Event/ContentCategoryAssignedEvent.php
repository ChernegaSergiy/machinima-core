<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Event;

use Morfeditorial\MachinimaCoreBundle\Entity\Category;
use Morfeditorial\MachinimaCoreBundle\Entity\Content;
use Symfony\Contracts\EventDispatcher\Event;

final class ContentCategoryAssignedEvent extends Event
{
    public function __construct(
        private readonly Content $content,
        private readonly Category $category,
    ) {
    }

    public function getContent(): Content
    {
        return $this->content;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }
}
