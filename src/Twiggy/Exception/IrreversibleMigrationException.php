<?php

namespace Twiggy\Exception;

class IrreversibleMigrationException extends \Exception
{
    public function __construct($message = 'This migration cannot be rolled back.')
    {
        parent::__construct($message);
    }
}