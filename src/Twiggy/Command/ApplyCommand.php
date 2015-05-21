<?php

namespace Twiggy\Command;

use Twiggy\Twiggy;
use Twiggy\Configuration;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class ApplyCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('apply')
            ->setDescription('Apply migration(s)')
            ->addArgument(
                'ids',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'Which migrations to apply (separate IDs by a space)'
            );
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $twiggy = $this->getApplication()->getTwiggy();
        $ids = $input->getArgument('ids');
        $count = count($ids);

        foreach ($ids as $index => $id) {
            if ($output->isVerbose()) {
                $startTime = microtime(true);
            }

            $migration = $twiggy->get($id);
            $twiggy->apply($migration);

            $index++;
            $output->writeLn("Applied migration $id. ($index / $count)");

            if ($output->isVerbose()) {
                $endTime = microtime(true);
                $output->writeLn('Took ' . number_format($endTime - $startTime) . ' seconds.');
            }
        }
    }
}