<?php

namespace App\Command\Blog\Post;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Repository\Blog\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class DeleteRemovedCommand extends Command
{
    protected static $defaultName =         'app:blog:posts:delete-removed';
    protected static $defaultDescription =  'Delete posts that were removed more than the configured number of days ago';

    private int|bool|null $restoreDaysLimit;

    public function __construct(
        private ContainerBagInterface   $parameters,
        private EntityManagerInterface  $em,
        private PostRepository          $postsRepository
    )
    {
        $this->restoreDaysLimit = $this->parameters->get('app.blog_posts.restore_days_limit');

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->restoreDaysLimit) {
            $output->writeln('Parameter "app.blog_posts.restore_days_limit" is not configured');

            return Command::SUCCESS;
        }

        $datetime = (new \DateTime())->modify('- ' . $this->restoreDaysLimit . ' days');
        $posts = $this->postsRepository->findRemovedBefore($datetime);

        if (!$posts) {
            $output->writeln('No posts to delete');

            return Command::SUCCESS;
        }

        foreach ($posts as $post) {
            foreach ($post->getComments()->toArray() as $comment) {
                $this->em->remove($comment);
            }

            $this->em->remove($post);
        }

        $this->em->flush();

        $output->writeln(sprintf('%s posts deleted', count($posts)));

        return Command::SUCCESS;
    }
}
