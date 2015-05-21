<?php

namespace Twiggy\Command;

use Twiggy\Twiggy;
use Twiggy\Configuration;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class RollbackCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('rollback')
            ->setDescription('Rollback migration changes')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'Which migration to rollback'
            );
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $twiggy = $this->getApplication()->getTwiggy();
        $id = $input->getArgument('id');

        if ($output->isVerbose()) {
            $startTime = microtime(true);
        }

        $migration = $twiggy->get($id);
        $twiggy->rollback($migration);

        $output->writeLn("Rolled back migration $id.");

        if ($output->isVerbose()) {
            $endTime = microtime(true);
            $output->writeLn('Took ' . number_format($endTime - $startTime) . ' seconds.');
        }
    }
}