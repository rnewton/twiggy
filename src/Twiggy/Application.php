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


class Application extends \Symfony\Component\Console\Application
{
    /**
     * @var Twiggy
     */
    private $twiggy;

    const TWIGGY_VERSION = '0.1';


    public function __construct($configPath, $configType = 'json')
    {
        // Setup application
        parent::__construct('twiggy', self::TWIGGY_VERSION);

        // Parse configuration file
        switch ($configType) {
            case 'json':
                $config = new Configuration(json_decode(file_get_contents($configPath), true));
                break;

            case 'yaml':
                $config = new Configuration(yaml_parse_file($configPath));
                break;

            default:
                throw new Exception("Unknown configuration file type: $configType. Valid types are json or yaml.");
        }

        $this->twiggy = new Twiggy($config);
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
     * @return Twiggy
     */
    public function getTwiggy()
    {
        return $this->twiggy;
    }
}