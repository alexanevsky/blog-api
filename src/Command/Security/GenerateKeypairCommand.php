<?php

namespace App\Command\Security;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateKeypairCommand extends Command
{
    protected static $defaultName =         'app:security:generate-jwt-keypair';
    protected static $defaultDescription =  'Generate JWT public and private keys and save them';

    private string $dir;

    public function __construct(
        private ContainerBagInterface $parameters
    )
    {
        $this->dir = $parameters->get('app.dir.jwt_keys');

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0777, true);
        }

        if (!is_writable($this->dir)) {
            $output->writeln(sprintf('Can not write keys to "%s"', $this->dir));
            return Command::FAILURE;
        }

        $publicKeyFilename =  $this->dir . '/public.pem';
        $privateKeyFilename = $this->dir . '/private.pem';

        exec(sprintf('openssl genrsa -out "%s" 2048 2>/dev/null', $privateKeyFilename));
        exec(sprintf('openssl rsa -in "%s" -pubout -out "%s" 2>/dev/null', $privateKeyFilename, $publicKeyFilename));
        exec(sprintf('chmod 644 "%s"', $publicKeyFilename));
        exec(sprintf('chmod 644 "%s"', $privateKeyFilename));

        $output->writeln(sprintf('Public key is saved to "%s"', $publicKeyFilename));
        $output->writeln(sprintf('Private key is saved to "%s"', $privateKeyFilename));

        return Command::SUCCESS;
    }
}
