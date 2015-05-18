<?php

namespace Twiggy\Exception

class MissingMigrationDirectoryException extends Exception
{
    public function __construct($expected)
    {
        parent::__construct("Migration directory doesn't exist at $expected.");
    }
}