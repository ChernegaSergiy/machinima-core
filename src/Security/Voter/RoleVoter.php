<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Security\Voter;

use Morfeditorial\MachinimaCoreBundle\Entity\Role;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class RoleVoter extends Voter
{
    public const VIEW = 'ROLE_VIEW';
    public const EDIT = 'ROLE_EDIT';
    public const DELETE = 'ROLE_DELETE';

    public function __construct(
        private Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof Role;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        return $this->security->isGranted('ROLE_ADMIN');
    }
}
