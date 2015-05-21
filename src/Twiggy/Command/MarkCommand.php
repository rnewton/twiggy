<?php

namespace Twiggy\Command;

use Twiggy\Twiggy;
use Twiggy\Configuration;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class MarkCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('mark')
            ->setDescription('Mark a migration as run')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'Which migration to mark'
            );
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $twiggy = $this->getApplication()->getTwiggy();
        $id = $input->getArgument('id');

        $migration = $twiggy->get($id);
        $twiggy->mark($migration);

        $output->writeLn("Marked migration $id as run.");
    }
}