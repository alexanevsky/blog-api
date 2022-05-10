<?php

namespace App\Controller\Blog;

use App\Component\Response\JsonResponse\AccessDeniedResponse;
use App\Component\Response\JsonResponse\DeletedResponse;
use App\Component\Response\JsonResponse\FailureResponse;
use App\Component\Response\JsonResponse\JsonResponse;
use App\Component\Response\JsonResponse\NotFoundResponse;
use App\Component\Response\JsonResponse\SuccessResponse;
use App\Controller\AbstractController;
use App\Entity\Blog\Comment;
use App\Normalizer\Blog\CommentCollectionNormalizer;
use App\Normalizer\Blog\CommentNormalizer;
use App\Repository\Blog\CommentRepository;
use App\Resolver\Blog\CommentResolverBuilder;
use App\Security\Voter\Blog\CommentVoter;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/blog/comments')]
class CommentController extends AbstractController
{
    public function __construct(
        private CommentRepository       $commentsRepository,
        private CommentResolverBuilder  $commentResolverBuilder
    )
    {}

    #[Route(path: '/{id<[\d]+>}', methods: ['GET'])]
    public function comment(int $id): JsonResponse
    {
        $comment = $this->commentsRepository->findOneById($id);

        if (!$comment) {
            return new NotFoundResponse('blog_comments.messages.comment.not_found');
        } elseif (!$this->isGranted(CommentVoter::ATTR_VIEW, $comment)) {
            if ($comment->isRemoved()) {
                return new DeletedResponse('blog_comments.messages.comment.removed');
            } else {
                return new AccessDeniedResponse('blog_comments.messages.comment.access_denied', needAuth: !$this->isLogged());
            }
        }

        return new SuccessResponse(data: [
            'comment' => $this->normalize(CommentNormalizer::class, $comment, [
                'author',
                'post',
                'parent_comment',
                'permissions'
            ])
        ]);
    }

    #[Route(path: '/{id<[\d]+>}/replies', methods: ['GET'])]
    public function replies(int $id): JsonResponse
    {
        $comment = $this->commentsRepository->findOneById($id);

        if (!$comment) {
            return new NotFoundResponse('blog_comments.messages.comment.not_found');
        } elseif (!$this->isGranted(CommentVoter::ATTR_VIEW, $comment)) {
            return new AccessDeniedResponse('blog_comments.messages.comment.access_denied', needAuth: !$this->isLogged());
        }

        return new SuccessResponse(data: [
            'comments' => $this->normalize(CommentCollectionNormalizer::class, $comment->getChildrenComments(), [
                'author',
                'parent_comment',
                'permissions'
            ])
        ]);
    }

    #[Route(path: '/{id<[\d]+>}', methods: ['PATCH'])]
    #[Route(path: '/{id<[\d]+>}/update', methods: ['GET', 'POST'])]
    public function update(int $id): JsonResponse
    {
        $comment = $this->commentsRepository->findOneById($id);

        if (!$comment) {
            return new NotFoundResponse('blog_comments.messages.comment.not_found');
        } elseif (!$this->isGranted(CommentVoter::ATTR_UPDATE, $comment)) {
            return new AccessDeniedResponse('blog_comments.messages.comment_update.access_denied', needAuth: !$this->isLogged());
        }

        $resolver = $this->commentResolverBuilder->build($comment);

        if ($this->isRequestMethod('GET')) {
            return new SuccessResponse(data: [
                'fields' => $resolver->getRequirements()
            ]);
        }

        $result = $resolver->resolve($this->decodeRequest());

        if (!$result->isValid()) {
            return new FailureResponse('blog_comments.messages.comment_update.failed', errors: $result->getFirstErrors());
        }

        $result->handleEntity();

        $comment->setUpdatedNow();

        $this->getDoctrineManager()->persist($comment);
        $this->getDoctrineManager()->flush();

        return new SuccessResponse('blog_comments.messages.comment_update.updated', data: [
            'comment' => $this->normalize(CommentNormalizer::class, $comment)
        ]);
    }

    #[Route(path: '/{id<[\d]+>}/remove', methods: ['POST'])]
    public function remove(int $id): JsonResponse
    {
        $comment = $this->commentsRepository->findOneById($id);

        if (!$comment) {
            return new NotFoundResponse('blog_comments.messages.comment.not_found');
        } elseif (!$this->isGranted(CommentVoter::ATTR_REMOVE, $comment)) {
            return new AccessDeniedResponse('blog_comments.messages.comment_remove.access_denied', needAuth: !$this->isLogged());
        }

        $comment
            ->setRemoved(true)
            ->setRemovedNow();

        $this->getDoctrineManager()->persist($comment);
        $this->getDoctrineManager()->flush();

        return new SuccessResponse('blog_comments.messages.comment_remove.removed');
    }

    #[Route(path: '/{id<[\d]+>}/restore', methods: ['POST'])]
    public function restore(int $id): JsonResponse
    {
        $comment = $this->commentsRepository->findOneById($id);

        if (!$comment) {
            return new NotFoundResponse('blog_comments.messages.comment.not_found');
        } elseif (!$this->isGranted(CommentVoter::ATTR_RESTORE, $comment)) {
            return new AccessDeniedResponse('blog_comments.messages.comment_restore.access_denied', needAuth: !$this->isLogged());
        }

        $comment
            ->setRemoved(false)
            ->setRemovedAt(null);

        $this->getDoctrineManager()->persist($comment);
        $this->getDoctrineManager()->flush();

        return new SuccessResponse('blog_comments.messages.comment_restore.restored');
    }

    #[Route(path: '/{id<[\d]+>}', methods: ['DELETE'])]
    #[Route(path: '/{id<[\d]+>}/delete', methods: ['POST'])]
    public function delete(int $id)
    {
        $comment = $this->commentsRepository->findOneById($id);

        if (!$comment) {
            return new NotFoundResponse('blog_comments.messages.comment.not_found');
        } elseif (!$this->isGranted(CommentVoter::ATTR_DELETE, $comment)) {
            return new AccessDeniedResponse('blog_comments.messages.comment_delete.access_denied', needAuth: !$this->isLogged());
        }

        foreach ($comment->getChildrenComments()->toArray() as $child) {
            $this->getDoctrineManager()->remove($child);
        }

        $this->getDoctrineManager()->remove($comment);
        $this->getDoctrineManager()->flush();

        return new SuccessResponse('blog_comments.messages.comment_delete.deleted');
    }

    #[Route(path: '/{id<[\d]+>}/replies', methods: ['POST'])]
    #[Route(path: '/{id<[\d]+>}/reply', methods: ['GET', 'POST'])]
    public function reply(int $id): JsonResponse
    {
        $comment = $this->commentsRepository->findOneById($id);

        if (!$comment) {
            return new NotFoundResponse('blog_comments.messages.comment.not_found');
        } elseif (!$this->isGranted(CommentVoter::ATTR_REPLY, $comment)) {
            return new AccessDeniedResponse('blog_comments.messages.comment_create.access_denied', needAuth: !$this->isLogged());
        }

        $reply = new Comment();
        $resolver = $this->commentResolverBuilder->build($reply);

        if ($this->isRequestMethod('GET')) {
            return new SuccessResponse(data: [
                'fields' => $resolver->getRequirements()
            ]);
        }

        $result = $resolver->resolve($this->decodeRequest());

        if (!$result->isValid()) {
            return new FailureResponse('blog_comments.messages.comment_create.failed', errors: $result->getFirstErrors());
        }

        $result->handleEntity();

        $reply
            ->setParentComment($comment)
            ->setPost($comment->getPost())
            ->setAuthor($this->getUser());

        $this->getDoctrineManager()->persist($reply);
        $this->getDoctrineManager()->flush();

        return new SuccessResponse('blog_comments.messages.comment_create.created', data: [
            'comment' => $this->normalize(CommentNormalizer::class, $reply)
        ]);
    }
}
