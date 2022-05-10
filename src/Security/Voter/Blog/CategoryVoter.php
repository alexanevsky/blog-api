<?php

namespace App\Security\Voter\Blog;

use App\Entity\User\User;
use App\Entity\Blog\Category;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CategoryVoter extends Voter
{
    public const ATTR_VIEW =    'view';
    public const ATTR_UPDATE =  'update';
    public const ATTR_DELETE =  'delete';

    public const ATTRIBUTES = [
        self::ATTR_VIEW,
        self::ATTR_UPDATE,
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
        return $subject instanceof Category;
    }

    /**
     * @param Category $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User|null */
        $user = $token->getUser() ?: null;

        if (!$subject instanceof Category) {
            return false;
        } elseif (!in_array($attribute, self::ATTRIBUTES, true)) {
            return false;
        } elseif (!in_array($attribute, self::TOKEN_USER_IS_OPTIONAL, true) && !$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::ATTR_VIEW:
                return $subject->isActive() || $user?->hasAnyRole([User::ROLE_ADMIN, User::ROLE_BLOG_AUTHOR, User::ROLE_BLOG_MANAGER]);
            case self::ATTR_UPDATE:
            case self::ATTR_DELETE:
                return $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_BLOG_MANAGER]);
        }

        return false;
    }
}
