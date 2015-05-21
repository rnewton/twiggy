<?php

namespace Twiggy\Command;

use Twiggy\Twiggy;
use Twiggy\Configuration;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;


class ListCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('ls')
            ->setDescription('List migrations by the given criteria')
            ->addOption(
               'description',
               'd',
               InputOption::VALUE_REQUIRED,
               'Search on description.'
            )
            ->addOption(
               'ran',
               'r',
               InputOption::VALUE_REQUIRED,
               'Show "ran", "unran" or "all" migrations. Defaults to "unran".'
            )
            ->addOption(
               'ticket',
               't',
               InputOption::VALUE_REQUIRED,
               'Search on ticket.'
            )
            ->addOption(
               'author',
               'a',
               InputOption::VALUE_REQUIRED,
               'Search on author.'
            )
            ->addOption(
               'id',
               'i',
               InputOption::VALUE_REQUIRED,
               'Search on migration ID.'
            );
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Translate params into an array format
        $searchParams = [
            'description' => $input->getOption('description'),
            'ran' => $input->getOption('ran') ? $input->getOption('ran') : 'unran', // Default to only showing unran migrations
            'ticket' => $input->getOption('ticket'),
            'author' => $input->getOption('author'),
            'id' => $input->getOption('id')
        ];

        // Grab all of our migrations
        $migrations = $this->getApplication()->getTwiggy()->getAll($searchParams);

        $formatted = [];
        foreach ($migrations as $migration) {
            $formatted[] = [
                $migration->getId(),
                $this->formatDescription($migration->getDescription()),
                $migration->getAuthor(),
                $migration->getTicket(),
                $this->formatRunDate($migration->getRunDate())
            ];
        }

        // Create a table for output
        $table = new Table($output);
        $table
            ->setHeaders(['Migration ID', 'Description', 'Author', 'Ticket', 'Run Date'])
            ->setRows($formatted)
            ->render();
    }


    private function formatDescription($description) 
    {
        if ('Missing migration file.' == $description) {
            return "<error>$description</error>";
        } else if (60 < strlen($description)) {
            return substr($description, 0, 60) . '…';
        } else {
            return $description;
        }
    }


    private function formatRunDate($runDate) 
    {
        if (is_a($runDate, 'DateTime')) {
            return '<info>' . $runDate->format('Y-m-d') . '</info>';
        } else {
            return '<fg=red>✘</fg=red>';
        }
    }
}