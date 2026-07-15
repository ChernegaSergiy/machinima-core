<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Event;

use Morfeditorial\MachinimaCoreBundle\Entity\Category;
use Symfony\Contracts\EventDispatcher\Event;

final class CategoryDeletedEvent extends Event
{
    public function __construct(
        private readonly Category $category,
    ) {
    }

    public function getCategory(): Category
    {
        return $this->category;
    }
}
