<?php

namespace App\Command\User;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Repository\User\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class EraseRemovedCommand extends Command
{
    protected static $defaultName =         'app:users:erase-removed';
    protected static $defaultDescription =  'Erase users that were removed more than the configured number of days ago';

    private int|bool|null $restoreDaysLimit;

    public function __construct(
        private ContainerBagInterface   $parameters,
        private EntityManagerInterface  $em,
        private UserRepository          $usersRepository
    )
    {
        $this->restoreDaysLimit = $this->parameters->get('app.users.restore_days_limit');

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->restoreDaysLimit) {
            $output->writeln('Parameter "app.users.restore_days_limit" is not configured');

            return Command::SUCCESS;
        }

        $datetime = (new \DateTime())->modify('- ' . $this->restoreDaysLimit . ' days');
        $users = $this->usersRepository->findRemovedBefore($datetime);

        if (!$users) {
            $output->writeln('No users to erase');

            return Command::SUCCESS;
        }

        foreach ($users as $user) {
            $user->erase();

            $this->em->persist($user);
        }

        $this->em->flush();

        $output->writeln(sprintf('%s users erased', count($users)));

        return Command::SUCCESS;
    }
}
