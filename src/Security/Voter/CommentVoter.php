<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Security\Voter;

use Morfeditorial\MachinimaCoreBundle\Entity\Comment;
use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CommentVoter extends Voter
{
    public const EDIT = 'COMMENT_EDIT';
    public const DELETE = 'COMMENT_DELETE';

    public function __construct(
        private Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE], true)
            && $subject instanceof Comment;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var Comment $subject */
        if ($this->security->isGranted('ROLE_MODERATOR')) {
            return true;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $isOwner = $subject->getUser() && (int) $user->getId() === (int) $subject->getUser()->getId();

        return match ($attribute) {
            self::EDIT => $isOwner,
            self::DELETE => $isOwner,
            default => false,
        };
    }
}
