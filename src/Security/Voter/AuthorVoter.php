<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Security\Voter;

use Morfeditorial\MachinimaCoreBundle\Entity\Author;
use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AuthorVoter extends Voter
{
    public const VIEW = 'AUTHOR_VIEW';
    public const EDIT = 'AUTHOR_EDIT';
    public const DELETE = 'AUTHOR_DELETE';

    public function __construct(
        private Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof Author;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var Author $subject */
        if ($this->security->isGranted('ROLE_MODERATOR')) {
            return true;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $isOwner = $subject->getUser() && (int) $user->getId() === (int) $subject->getUser()->getId();

        return match ($attribute) {
            self::VIEW => 'private' !== $subject->getState() || $isOwner,
            self::EDIT, self::DELETE => $isOwner,
            default => false,
        };
    }
}
