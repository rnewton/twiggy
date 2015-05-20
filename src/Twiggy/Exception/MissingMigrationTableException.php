<?php

namespace Twiggy\Exception;

class MissingMigrationTableException extends \Exception
{
    public function __construct()
    {
        parent::__construct("Migration table doesn't exist in database.");
    }
}