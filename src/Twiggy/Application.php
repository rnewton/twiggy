<?php

namespace Twiggy;

use Twiggy\Twiggy;
use Twiggy\Configuration;
use Twiggy\Command\ListCommand;
use Twiggy\Command\ApplyCommand;
use Twiggy\Command\RollbackCommand;
use Twiggy\Command\TestCommand;
use Twiggy\Command\MarkCommand;
use Twiggy\Command\CreateCommand;
use Twiggy\Command\RemoveCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;


class Application extends \Symfony\Component\Console\Application
{
    /**
     * @var Twiggy
     */
    private $twiggy;

    const TWIGGY_VERSION = '0.1';


    public function __construct()
    {
        // Setup application
        parent::__construct('twiggy', self::TWIGGY_VERSION);

        // List unran migrations by default
        $this->setDefaultCommand('ls');
    }


    protected function getDefaultCommands()
    {
        // Get defaults from parent (list, help)
        $defaultCommands = parent::getDefaultCommands();

        // Extend with twiggy specific commands
        $defaultCommands[] = new ListCommand();
        $defaultCommands[] = new ApplyCommand();
        $defaultCommands[] = new RollbackCommand();
        $defaultCommands[] = new TestCommand();
        $defaultCommands[] = new MarkCommand();
        $defaultCommands[] = new CreateCommand();
        $defaultCommands[] = new RemoveCommand();

        return $defaultCommands;
    }


    /**
     * Gets the default input definition.
     *
     * @return InputDefinition An InputDefinition instance
     */
    protected function getDefaultInputDefinition()
    {
        return new InputDefinition([
            new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),

            new InputOption('--help',           '-h', InputOption::VALUE_NONE, 'Display this help message'),
            new InputOption('--quiet',          '-q', InputOption::VALUE_NONE, 'Do not output any message'),
            new InputOption('--verbose',        '-v|vv|vvv', InputOption::VALUE_NONE, 'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug'),
            new InputOption('--version',        '-V', InputOption::VALUE_NONE, 'Display this application version'),
            new InputOption('--ansi',           '',   InputOption::VALUE_NONE, 'Force ANSI output'),
            new InputOption('--no-ansi',        '',   InputOption::VALUE_NONE, 'Disable ANSI output'),

            new InputOption('--config',         '-c', InputOption::VALUE_REQUIRED, 'Path to the configuration file'),
            new InputOption('--db_dsn',         '-H', InputOption::VALUE_REQUIRED, 'Set the Database DSN'),
            new InputOption('--db_user',        '-U', InputOption::VALUE_REQUIRED, 'Set the Database user (Default: None, can be in DSN)'),
            new InputOption('--db_pass',        '-P', InputOption::VALUE_REQUIRED, 'Set the Database password (Default: None, can be in DSN)'),
            new InputOption('--db_options',     '-O', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Set the Database options separated by spaces (Default: None)'),
            new InputOption('--directory',      '-D', InputOption::VALUE_REQUIRED, 'Set the path to the directory where migrations are stored (Default: ./migrations)'),
            new InputOption('--table',          '-T', InputOption::VALUE_REQUIRED, 'Set the name of the database table where migrations are stored (Default: migrations)'),
            new InputOption('--id_format',      '-I', InputOption::VALUE_REQUIRED, 'Set the regex used for migration ids (Default: /\d{8}_\d{6}/)')
        ]);
    }


    /**
     * Configures the input and output instances based on the user arguments and options.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function configureIO(InputInterface $input, OutputInterface $output)
    {
        parent::configureIO($input, $output);

        $twiggyConfig = [];

        // Configuration file
        if ($input->hasParameterOption('--config') || $input->hasParameterOption('-c')) {
            $file = $input->getParameterOption('--config') ?: $input->getParameterOption('-c');
            $extension = pathinfo($file)['extension'];

            // Parse configuration file
            switch ($extension) {
                case 'json':
                    $twiggyConfig = json_decode(file_get_contents($file), true);
                    break;
                case 'yaml':
                    $twiggyConfig = yaml_parse_file($file);
                    break;
                default:
                    throw new Exception("Unknown configuration file type: $extension. Valid types are json or yaml.");
            }
        // Individual options
        } else {
            if ($input->hasParameterOption('--db_dsn')) {
                $twiggyConfig[Configuration::DATABASE_DSN] = $input->getParameterOption('--db_dsn') ?: $input->getParameterOption('-H');
            }

            if ($input->hasParameterOption('--db_user')) {
                $twiggyConfig[Configuration::DATABASE_USER] = $input->getParameterOption('--db_user') ?: $input->getParameterOption('-U');
            }

            if ($input->hasParameterOption('--db_pass')) {
                $twiggyConfig[Configuration::DATABASE_PASSWORD] = $input->getParameterOption('--db_pass') ?: $input->getParameterOption('-P');
            }

            if ($input->hasParameterOption('--db_options')) {
                $twiggyConfig[Configuration::DATABASE_OPTIONS] = $input->getParameterOption('--db_options') ?: $input->getParameterOption('-O');
            }

            if ($input->hasParameterOption('--directory')) {
                $twiggyConfig[Configuration::MIGRATION_DIRECTORY] = $input->getParameterOption('--directory') ?: $input->getParameterOption('-D');
            }

            if ($input->hasParameterOption('--table')) {
                $twiggyConfig[Configuration::MIGRATION_TABLE] = $input->getParameterOption('--table') ?: $input->getParameterOption('-T');
            }

            if ($input->hasParameterOption('--id_format')) {
                $twiggyConfig[Configuration::MIGRATION_ID_FORMAT] = $input->getParameterOption('--id_format') ?: $input->getParameterOption('-I');
            }
        }

        try {
            $this->twiggy = new Twiggy(new Configuration($twiggyConfig));
        } catch (\Exception $e) {
            $this->renderException($e, $output);
        }
    }


    /**
     * @return Twiggy
     */
    public function getTwiggy()
    {
        return $this->twiggy;
    }
}