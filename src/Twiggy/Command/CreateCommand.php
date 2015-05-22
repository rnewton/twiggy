<?php

namespace Twiggy\Command;

use Twiggy\Twiggy;
use Twiggy\Configuration;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;


class CreateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('create')
            ->setDescription('Create a new migration')
            ->addOption(
               'description',
               'd',
               InputOption::VALUE_REQUIRED,
               'Description of what the migration will do.'
            )
            ->addOption(
               'ticket',
               't',
               InputOption::VALUE_REQUIRED,
               'Ticket relevant to this migration.'
            )
            ->addOption(
               'author',
               'a',
               InputOption::VALUE_REQUIRED,
               'Author of this migration (if not specified, it will attempt to be filled in from unix username).'
            );
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $description = $input->getOption('description');
        $ticket = $input->getOption('ticket');
        $author = $input->getOption('author');

        // Fill in author with process owner if not given.
        if (!$author) {
            // Windows
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $author = get_current_user();
            // Unix-based
            } else {
                $author = posix_getpwuid(posix_geteuid())['name'];
            }
        }

        $migration = $this->getApplication()->getTwiggy()->create($description, $ticket, $author);

        // Show the newly created migration in the list
        $command = $this->getApplication()->find('ls');

        $args = [
            'command' => 'ls',
            '--id' => $migration->getId()
        ];

        $command->run(new ArrayInput($args), $output);
    }
}