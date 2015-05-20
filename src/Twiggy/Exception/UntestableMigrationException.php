<?php

namespace Twiggy\Exception;

class UntestableMigrationException extends \Exception
{
    public function __construct(Migration $migration)
    {
        parent::__construct('Migration ' . $migration->getId() . ' is not testable.');
    }
}