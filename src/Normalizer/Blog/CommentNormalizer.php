<?php

namespace App\Normalizer\Blog;

use App\Component\Normalizer\AbstractNormalizer;
use App\Component\Normalizer\NormalizerFactory;
use App\Entity\Blog\Category;
use App\Entity\Blog\Comment;
use App\Normalizer\User\UserPrimaryNormalizer;
use App\Security\Voter\Blog\CategoryVoter;
use App\Security\Voter\Blog\CommentVoter;
use Symfony\Component\Security\Core\Security;

class CommentNormalizer extends AbstractNormalizer
{
    public function __construct(
        protected NormalizerFactory $normalizer,
        protected Security          $security
    )
    {}

    public function supports($data): bool
    {
        return $data instanceof Comment;
    }

    /**
     * @param Comment $data
     */
    public function normalize($data, array $includes = []): array
    {
        $output = [
            'id' =>             $data->getId(),
            'content' =>        !$data->isRemoved() || $this->security->isGranted(CommentVoter::ATTR_VIEW, $data) ? $data->getContent() : null,
            'is_removed' =>     $data->isRemoved(),
            'created_at' =>     $data->getCreatedAt()?->format('c'),
            'updated_at' =>     $data->getUpdatedAt()?->format('c'),
            'deleted_at' =>     $data->getRemovedAt()?->format('c')
        ];

        if (in_array('author', $includes)) {
            $output['author'] = !$data->hasAuthor() || $data->getAuthor()->isRemoved() || $data->getAuthor()->isErased()
                ? null
                : $this->normalizer->normalize(UserPrimaryNormalizer::class, $data->getAuthor(), $this->extractIncludes($includes, 'author'));
        }

        if (in_array('post', $includes)) {
            $output['post'] = $this->normalizer->normalize(PostMainNormalizer::class, $data->getPost(), $this->extractIncludes($includes, 'post'));
        }

        if (in_array('parent_comment', $includes)) {
            $output['parent_comment'] = $this->normalizer->normalize(CommentNormalizer::class, $data->getParentComment(), $this->extractIncludes($includes, 'parent_comment'));
        }

        if (in_array('children_comments', $includes)) {
            $output['children_comments'] = $this->normalizer->normalize(CommentCollectionNormalizer::class, $data->getChildrenComments(), $this->extractIncludes($includes, 'children_comments'));
        }

        if (in_array('permissions', $includes)) {
            $output['permissions'] = array_combine(CommentVoter::ATTRIBUTES, array_map(function (string $attribute) use ($data) {
                return $this->security->isGranted($attribute, $data);
            }, CommentVoter::ATTRIBUTES));
        }

        return $output;
    }
}
