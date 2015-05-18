<?php

namespace Twiggy\Exception

class MissingMigrationException extends Exception
{
    public function __construct()
    {
        parent::__construct('Missing migration file');
    }
}