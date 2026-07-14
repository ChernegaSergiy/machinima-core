<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Contract;

use Morfeditorial\MachinimaCoreBundle\Entity\User;

interface UserNotificationAddressResolver
{
    public function resolveAddress(User $user, string $channel): ?string;
}
