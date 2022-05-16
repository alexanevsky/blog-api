<?php

namespace App\Controller\Blog;

use App\Component\File\ImageResolver;
use App\Component\Response\JsonResponse\AccessDeniedResponse;
use App\Component\Response\JsonResponse\DeletedResponse;
use App\Component\Response\JsonResponse\FailureResponse;
use App\Component\Response\JsonResponse\JsonResponse;
use App\Component\Response\JsonResponse\NotFoundResponse;
use App\Component\Response\JsonResponse\SuccessResponse;
use App\Controller\AbstractController;
use App\Entity\Blog\Comment;
use App\Entity\Blog\Post;
use App\Entity\User\User;
use App\Normalizer\Blog\CommentCollectionNormalizer;
use App\Normalizer\Blog\CommentNormalizer;
use App\Normalizer\Blog\PostMainCollectionNormalizer;
use App\Normalizer\Blog\PostNormalizer;
use App\Repository\Blog\CommentRepository;
use App\Repository\Blog\PostRepository;
use App\Resolver\Blog\CommentResolverBuilder;
use App\Resolver\Blog\PostResolverBuilder;
use App\Security\Voter\Blog\PostVoter;
use App\Security\Voter\SecurityVoter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/blog/posts')]
class PostController extends AbstractController
{
    public function __construct(
        private CommentRepository       $commentsRepository,
        private CommentResolverBuilder  $commentResolverBuilder,
        private PostRepository          $postsRepository,
        private PostResolverBuilder     $postResolverBuilder
    )
    {}

    #[Route(path: '', methods: ['GET'])]
    public function all(): JsonResponse
    {
        [$postsOffset, $postsLimit] = [
            (int) $this->getQueryParameter('offset', 0),
            (int) $this->getQueryParameter('limit', PostRepository::PAGE_LIMIT)
        ];

        $posts = $this->postsRepository->findPublishedPaginated($postsOffset, $postsLimit);

        return new SuccessResponse(data: [
            'posts' => $this->normalize(PostMainCollectionNormalizer::class, $posts, [
                'author',
                'categories',
                'comments_count',
                'permissions'
            ]),
            'posts_meta' => $posts->getMeta()
        ]);
    }

    #[Route(path: '/unpublished', methods: ['GET'])]
    public function unpublished(): JsonResponse
    {
        if (!$this->isGrantedAny([User::ROLE_ADMIN, User::ROLE_BLOG_MANAGER])) {
            return new AccessDeniedResponse('blog_posts.messages.unpublished_posts.access_denied', needAuth: !$this->isLogged());
        }

        [$postsOffset, $postsLimit] = [
            (int) $this->getQueryParameter('offset', 0),
            (int) $this->getQueryParameter('limit', PostRepository::PAGE_LIMIT)
        ];

        $posts = $this->postsRepository->findUnpublishedPaginated($postsOffset, $postsLimit);

        return new SuccessResponse(data: [
            'posts' => $this->normalize(PostMainCollectionNormalizer::class, $posts, [
                'author',
                'categories',
                'comments_count',
                'permissions'
            ]),
            'posts_meta' => $posts->getMeta()
        ]);
    }

    #[Route(path: '/removed', methods: ['GET'])]
    public function removed(): JsonResponse
    {
        if (!$this->isGrantedAny([User::ROLE_ADMIN, User::ROLE_BLOG_MANAGER])) {
            return new AccessDeniedResponse('blog_posts.messages.posts_removed.access_denied', needAuth: !$this->isLogged());
        }

        [$postsOffset, $postsLimit] = [
            (int) $this->getQueryParameter('offset', 0),
            (int) $this->getQueryParameter('limit', PostRepository::PAGE_LIMIT)
        ];

        $posts = $this->postsRepository->findRemovedPaginated($postsOffset, $postsLimit);

        return new SuccessResponse(data: [
            'posts' => $this->normalize(PostMainCollectionNormalizer::class, $posts, [
                'author',
                'categories',
                'comments_count',
                'permissions'
            ]),
            'posts_meta' => $posts->getMeta()
        ]);
    }

    #[Route(path: '/{id<[\d]+>}', methods: ['GET'])]
    public function post(int $id): JsonResponse
    {
        $post = $this->postsRepository->findOneById($id);

        if (!$post) {
            return new NotFoundResponse('blog_posts.messages.post.not_found');
        } elseif (!$this->isGranted(PostVoter::ATTR_VIEW, $post)) {
            if ($post->isRemoved()) {
                return new DeletedResponse('blog_posts.messages.post.removed');
            } else {
                return new AccessDeniedResponse('blog_posts.messages.post.access_denied', needAuth: !$this->isLogged());
            }
        }

        return new SuccessResponse(data: [
            'post' => $this->normalize(PostNormalizer::class, $post, [
                'author',
                'categories',
                'comments_count',
                'permissions'
            ])
        ]);
    }

    #[Route(path: '', methods: ['POST'])]
    #[Route(path: '/create', methods: ['GET', 'POST'])]
    public function create(): JsonResponse
    {
        if (!$this->isGranted(SecurityVoter::ATTR_CREATE_BLOG_POST)) {
            return new AccessDeniedResponse('blog_posts.messages.post_create.access_denied', needAuth: !$this->isLogged());
        }

        $post = new Post();
        $resolver = $this->postResolverBuilder->build($post);

        if ($this->isRequestMethod('GET')) {
            return new SuccessResponse(data: [
                'fields' => $resolver->getRequirements()
            ]);
        }

        $result = $resolver->resolve($this->decodeRequest());

        if (!$result->isValid()) {
            return new FailureResponse('blog_posts.messages.post_create.failed', errors: $result->getFirstErrors());
        }

        $result->handleEntity();

        $post->setAuthor($this->getUser());

        $this->getDoctrineManager()->persist($post);
        $this->getDoctrineManager()->flush();

        return new SuccessResponse('blog_posts.messages.post_create.created', data: [
            'post' => $this->normalize(PostNormalizer::class, $post)
        ]);
    }

    #[Route(path: '/{id<[\d]+>}', methods: ['PATCH'])]
    #[Route(path: '/{id<[\d]+>}/update', methods: ['GET', 'POST'])]
    public function update(int $id): JsonResponse
    {
        $post = $this->postsRepository->findOneById($id);

        if (!$post) {
            return new NotFoundResponse('blog_posts.messages.post.not_found');
        } elseif (!$this->isGranted(PostVoter::ATTR_UPDATE, $post)) {
            return new AccessDeniedResponse('blog_posts.messages.post_update.access_denied', needAuth: !$this->isLogged());
        }

        $resolver = $this->postResolverBuilder->build($post);

        if ($this->isRequestMethod('GET')) {
            return new SuccessResponse(data: [
                'fields' => $resolver->getRequirements()
            ]);
        }

        $result = $resolver->resolve($this->decodeRequest());

        if (!$result->isValid()) {
            return new FailureResponse('blog_posts.messages.post_update.failed', errors: $result->getFirstErrors());
        }

        $result->handleEntity();

        $post->setUpdatedNow();

        $this->getDoctrineManager()->persist($post);
        $this->getDoctrineManager()->flush();

        return new SuccessResponse('blog_posts.messages.post_update.updated', data: [
            'post' => $this->normalize(PostNormalizer::class, $post)
        ]);
    }

    #[Route(path: '/{id<[\d]+>}/image', methods: ['POST'])]
    public function uploadImage(int $id)
    {
        $post = $this->postsRepository->findOneById($id);

        if (!$post) {
            return new NotFoundResponse('blog_posts.messages.post.not_found');
        } elseif (!$this->isGranted(PostVoter::ATTR_UPDATE, $post)) {
            return new AccessDeniedResponse('blog_posts.messages.post_image_upload.access_denied', needAuth: !$this->isLogged());
        }

        $image = (ImageResolver::fromRequest($this->getRequest()))
            ->setMaxSize(Post::IMAGE_MAX_SIZE);

        if (!$image->isValidMimeType()) {
            return new FailureResponse('common.errors.image.invalid_extension', [
                'extension' => strtoupper(implode(', ', $image->getAllowedExtensions()))
            ]);
        } elseif (!$image->isValidSize()) {
            return new FailureResponse('common.errors.image.big_size', [
                'size' => sprintf('%s B', $image->getMaxSize())
            ]);
        }

        if ($post->hasImage()) {
            (new Filesystem())->remove($this->getParameter('kernel.project_dir') . $post->getImagePathname());
        }

        $imageName = (string) (($post->getId() . ($post->hasAlias() ? '-' . $post->getAlias() : '')) . '.' . $image->getExtension());
        $image
            ->resizeMax(Post::IMAGE_MAX_WIDTH)
            ->save($this->getParameter('kernel.project_dir') . Post::IMAGE_PATH, $imageName);

        $post
            ->setImage($imageName)
            ->setUpdatedNow();

        $this->getDoctrineManager()->persist($post);
        $this->getDoctrineManager()->flush();

        return new SuccessResponse('posts.messages.post_image_upload.uploaded');
    }

    #[Route(path: '/{id<[\d]+>}/image', methods: ['DELETE'])]
    #[Route(path: '/{id<[\d]+>}/image/delete', methods: ['POST'])]
    public function deleteImage(int|string $id)
    {
        $post = $this->postsRepository->findOneById($id);

        if (!$post) {
            return new NotFoundResponse('blog_posts.messages.post.not_found');
        } elseif (!$this->isGranted(PostVoter::ATTR_UPDATE, $post)) {
            return new AccessDeniedResponse('blog_posts.messages.post_image_delete.access_denied', needAuth: !$this->isLogged());
        }

        if ($post->hasImage()) {
            (new Filesystem())->remove($this->getParameter('kernel.project_dir') . $post->getImagePathname());

            $post
                ->setImage('')
                ->setUpdatedNow();

            $this->getDoctrineManager()->persist($post);
            $this->getDoctrineManager()->flush();
        }

        return new SuccessResponse('users.messages.post_image_delete.deleted');
    }

    #[Route(path: '/{id<[\d]+>}/remove', methods: ['POST'])]
    public function remove(int $id): JsonResponse
    {
        $post = $this->postsRepository->findOneById($id);

        if (!$post) {
            return new NotFoundResponse('blog_posts.messages.post.not_found');
        } elseif (!$this->isGranted(PostVoter::ATTR_REMOVE, $post)) {
            return new AccessDeniedResponse('blog_posts.messages.post_remove.access_denied', needAuth: !$this->isLogged());
        }

        $post
            ->setRemoved(true)
            ->setRemovedNow();

        $this->getDoctrineManager()->persist($post);
        $this->getDoctrineManager()->flush();

        return new SuccessResponse('blog_posts.messages.post_remove.removed');
    }

    #[Route(path: '/{id<[\d]+>}/restore', methods: ['POST'])]
    public function restore(int $id): JsonResponse
    {
        $post = $this->postsRepository->findOneById($id);

        if (!$post) {
            return new NotFoundResponse('blog_posts.messages.post.not_found');
        } elseif (!$this->isGranted(PostVoter::ATTR_RESTORE, $post)) {
            return new AccessDeniedResponse('blog_posts.messages.post_restore.access_denied', needAuth: !$this->isLogged());
        }

        $post
            ->setRemoved(false)
            ->setRemovedAt(null);

        $this->getDoctrineManager()->persist($post);
        $this->getDoctrineManager()->flush();

        return new SuccessResponse('blog_posts.messages.post_restore.restored');
    }

    #[Route(path: '/{id<[\d]+>}', methods: ['DELETE'])]
    #[Route(path: '/{id<[\d]+>}/delete', methods: ['POST'])]
    public function delete(int $id)
    {
        $post = $this->postsRepository->findOneById($id);

        if (!$post) {
            return new NotFoundResponse('blog_posts.messages.post.not_found');
        } elseif (!$this->isGranted(PostVoter::ATTR_DELETE, $post)) {
            return new AccessDeniedResponse('blog_posts.messages.post_delete.access_denied', needAuth: !$this->isLogged());
        }

        foreach ($post->getComments()->toArray() as $comment) {
            $this->getDoctrineManager()->remove($comment);
        }

        $this->getDoctrineManager()->remove($post);
        $this->getDoctrineManager()->flush();

        return new SuccessResponse('blog_posts.messages.post_delete.deleted');
    }

    #[Route(path: '/{id<[\d]+>}/comments', methods: ['GET'])]
    public function comments(int $id): JsonResponse
    {
        $post = $this->postsRepository->findOneById($id);

        if (!$post) {
            return new NotFoundResponse('blog_posts.messages.post.not_found');
        } elseif (!$this->isGranted(PostVoter::ATTR_VIEW, $post)) {
            return new AccessDeniedResponse('blog_posts.messages.post.access_denied', needAuth: !$this->isLogged());
        }

        $comments = $this->commentsRepository->findByPost($post);

        return new SuccessResponse(data: [
            'comments' => $this->normalize(CommentCollectionNormalizer::class, $comments, [
                'author',
                'parent_comment',
                'permissions'
            ])
        ]);
    }

    #[Route(path: '/{id<[\d]+>}/comments', methods: ['POST'])]
    #[Route(path: '/{id<[\d]+>}/comments/add', methods: ['GET', 'POST'])]
    public function addComment(int $id): JsonResponse
    {
        $post = $this->postsRepository->findOneById($id);

        if (!$post) {
            return new NotFoundResponse('blog_posts.messages.post.not_found');
        } elseif (!$this->isGranted(PostVoter::ATTR_CREATE_COMMENT, $post)) {
            return new AccessDeniedResponse('blog_comments.messages.comment_create.access_denied', needAuth: !$this->isLogged());
        }

        $comment = new Comment();
        $resolver = $this->commentResolverBuilder->build($comment);

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

        $comment->setPost($post);
        $comment->setAuthor($this->getUser());

        $this->getDoctrineManager()->persist($comment);
        $this->getDoctrineManager()->flush();

        return new SuccessResponse('blog_comments.messages.comment_create.created', data: [
            'comment' => $this->normalize(CommentNormalizer::class, $comment)
        ]);
    }
}
