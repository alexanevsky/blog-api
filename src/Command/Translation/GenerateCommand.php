<?php

namespace App\Command\Translation;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Component\Translation\Generator as TranslationGenerator;

class GenerateCommand extends Command
{
    protected static $defaultName =         'app:translations:generate';
    protected static $defaultDescription =  'Generate translations files from language files';

    public function __construct(
        private TranslationGenerator $generator
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->generator->generate();

        return Command::SUCCESS;
    }
}
