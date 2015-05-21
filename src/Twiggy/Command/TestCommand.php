<?php

namespace Twiggy\Command;

use Twiggy\Twiggy;
use Twiggy\Configuration;
use Twiggy\Exception\UntestableMigrationException;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class TestCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('test')
            ->setDescription('Test a migration')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'Which migration to test'
            );
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $twiggy = $this->getApplication()->getTwiggy();
        $id = $input->getArgument('id');

        $dialog = $this->getHelper('dialog');

        if ($dialog->askConfirmation(
            $output,
            "<question>Does migration $id use any external resources? (Other databases, files, streams?)</question>"
        )) {
            throw new UntestableMigrationException($id);
        }

        if ($output->isVerbose()) {
            $startTime = microtime(true);
        }

        $migration = $twiggy->get($id);
        $twiggy->test($migration);

        $output->writeLn("Tested migration $id. Changes were not saved.");

        if ($output->isVerbose()) {
            $endTime = microtime(true);
            $output->writeLn('Took ' . number_format($endTime - $startTime) . ' seconds.');
        }
    }
}