<?php

namespace App\Resolver\Blog;

use Alexanevsky\DataResolver\EntityResolver;
use App\Component\Validator\ConstraintBuilder;
use App\Entity\Blog\Comment;

class CommentResolverBuilder
{
    public function __construct(
        private ConstraintBuilder $constraints
    )
    {}

    public function build(Comment $comment): EntityResolver
    {
        $resolver = new EntityResolver();

        $resolver->define('content', 'array')
            ->addConstraint($this->constraints->notBlank('blog_comments.errors.content.empty'));

        $resolver->handleEntity($comment);

        return $resolver;
    }
}
