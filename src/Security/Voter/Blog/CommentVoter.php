<?php

namespace App\Security\Voter\Blog;

use App\Entity\User\User;
use App\Entity\Blog\Comment;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CommentVoter extends Voter
{
    public const ATTR_VIEW =    'view';
    public const ATTR_REPLY =   'reply';
    public const ATTR_UPDATE =  'update';
    public const ATTR_REMOVE =  'remove';
    public const ATTR_RESTORE = 'restore';
    public const ATTR_DELETE =  'delete';

    public const ATTRIBUTES = [
        self::ATTR_VIEW,
        self::ATTR_REPLY,
        self::ATTR_UPDATE,
        self::ATTR_REMOVE,
        self::ATTR_RESTORE,
        self::ATTR_DELETE
    ];

    /**
     * List of votes in which a user from the authentication token is not required.
     */
    protected const TOKEN_USER_IS_OPTIONAL = [
        self::ATTR_VIEW
    ];

    protected function supports(string $attribute, $subject = null): bool
    {
        return $subject instanceof Comment;
    }

    /**
     * @param Comment $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User|null */
        $user = $token->getUser() ?: null;

        if (!$subject instanceof Comment) {
            return false;
        } elseif (!in_array($attribute, self::ATTRIBUTES, true)) {
            return false;
        } elseif (!in_array($attribute, self::TOKEN_USER_IS_OPTIONAL, true) && !$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::ATTR_VIEW:
                return $this->canView($subject, $user);
            case self::ATTR_REPLY:
                return !$user->isCommunicationBanned() && !$subject->isRemoved();
            case self::ATTR_UPDATE:
                return !$subject->isRemoved() && $this->canManipulate($subject, $user);
            case self::ATTR_REMOVE:
                return !$subject->isRemoved() && $this->canManipulate($subject, $user);
            case self::ATTR_RESTORE:
                return $subject->isRemoved() && $this->canManipulate($subject, $user);
            case self::ATTR_DELETE:
                return ($user?->hasAnyRole([User::ROLE_ADMIN, User::ROLE_BLOG_MANAGER])) ? true : false;
        }

        return false;
    }

    private function canView(Comment $comment, ?User $user): bool
    {
        return !$comment->isRemoved() || $this->canManipulate($comment, $user);
    }

    private function canManipulate(Comment $comment, ?User $user): bool
    {
        if ($user?->hasAnyRole([User::ROLE_ADMIN, User::ROLE_BLOG_MANAGER])) {
            return true;
        } elseif ($comment->getAuthor() && $comment->getAuthor() === $user) {
            return true;
        }

        return false;
    }
}
