<?php

require_once('vendor/autoload.php');

use Twiggy\Application;

$app = new Application('example_config.json');
$app->run();