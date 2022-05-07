<?php

namespace App\Security\Voter\Blog;

use App\Entity\User\User;
use App\Entity\Blog\Post;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PostVoter extends Voter
{
    public const ATTR_VIEW =            'view';
    public const ATTR_UPDATE =          'update';
    public const ATTR_REMOVE =          'remove';
    public const ATTR_RESTORE =         'restore';
    public const ATTR_DELETE =          'delete';
    public const ATTR_CREATE_COMMENT =  'create_comment';

    public const ATTRIBUTES = [
        self::ATTR_VIEW,
        self::ATTR_UPDATE,
        self::ATTR_REMOVE,
        self::ATTR_RESTORE,
        self::ATTR_DELETE,
        self::ATTR_CREATE_COMMENT
    ];

    /**
     * List of votes in which a user from the authentication token is not required.
     */
    protected const TOKEN_USER_IS_OPTIONAL = [
        self::ATTR_VIEW
    ];

    protected function supports(string $attribute, $subject = null): bool
    {
        return $subject instanceof Post;
    }

    /**
     * @param Post $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User|null */
        $user = $token->getUser() ?: null;

        if (!$subject instanceof Post) {
            return false;
        } elseif (!in_array($attribute, self::ATTRIBUTES, true)) {
            return false;
        } elseif (!in_array($attribute, self::TOKEN_USER_IS_OPTIONAL, true) && !$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::ATTR_VIEW:
                return $this->canView($subject, $user);
            case self::ATTR_UPDATE:
                return !$subject->isRemoved() && $this->canManipulate($subject, $user);
            case self::ATTR_REMOVE:
                return !$subject->isRemoved() && $this->canManipulate($subject, $user);
            case self::ATTR_RESTORE:
                return $subject->isRemoved() && $this->canManipulate($subject, $user);
            case self::ATTR_DELETE:
                return $this->canManipulate($subject, $user);
            case self::ATTR_CREATE_COMMENT:
                return !$user->isCommunicationBanned() && $this->canView($subject, $user);
        }

        return false;
    }

    private function canView(Post $post, ?User $user): bool
    {
        return ($post->isPublished() && !$post->isRemoved()) || $this->canManipulate($post, $user);
    }

    private function canManipulate(Post $post, ?User $user): bool
    {
        if ($user?->hasAnyRole([User::ROLE_ADMIN, User::ROLE_BLOG_MANAGER])) {
            return true;
        } elseif ($user?->hasRole(User::ROLE_BLOG_AUTHOR) && $post->getAuthor() && $post->getAuthor() === $user) {
            return true;
        }

        return false;
    }
}
