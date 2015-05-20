<?php

require_once('vendor/autoload.php');

use \Twiggy\Twiggy;
use \Twiggy\Configuration;
use \Twiggy\Exception\MissingMigrationDirectoryException;
use \Twiggy\Exception\UntestableMigrationException;
use \Twiggy\Exception\MissingMigrationException;
use \Twiggy\Exception\UnmetDependencyException;
use \Twiggy\Exception\MissingMigrationTableException;

$config = new Configuration([
    Configuration::DATABASE_DSN => 'pgsql:host=localhost;port=5432;dbname=homebrew_2;user=postgres;password=nach0!'
]);

$twiggy = new Twiggy($config);