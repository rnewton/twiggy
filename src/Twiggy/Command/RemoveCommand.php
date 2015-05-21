<?php

namespace Twiggy\Command;

use Twiggy\Twiggy;
use Twiggy\Configuration;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class RemoveCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('remove')
            ->setDescription('Remove a migration entirely')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'Which migration to remove'
            );
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $twiggy = $this->getApplication()->getTwiggy();
        $id = $input->getArgument('id');

        $migration = $twiggy->get($id);
        $twiggy->remove($migration);

        $output->writeLn("Removed migration $id.");
    }
}