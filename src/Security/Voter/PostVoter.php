<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Security\Voter;

use Morfeditorial\MachinimaCoreBundle\Entity\Content;
use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PostVoter extends Voter
{
    public const VIEW = 'POST_VIEW';
    public const EDIT = 'POST_EDIT';
    public const DELETE = 'POST_DELETE';

    public function __construct(
        private Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof Content;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var Content $subject */
        if ($this->security->isGranted('ROLE_MODERATOR')) {
            return true;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $isStaff = $this->isStaffMember($subject, $user);
        $isOwner = $subject->getCreatedBy() && (int) $subject->getCreatedBy()->getId() === (int) $user->getId();

        return match ($attribute) {
            self::VIEW => 'published' === $subject->getStatus() || $isStaff,
            self::EDIT, self::DELETE => $isStaff || $isOwner,
            default => false,
        };
    }

    private function isStaffMember(Content $content, User $user): bool
    {
        foreach ($content->getStaff() as $staffItem) {
            $author = $staffItem->getAuthor();
            if ($author && $author->getUser() && (int) $author->getUser()->getId() === (int) $user->getId()) {
                return true;
            }
        }

        return false;
    }
}
