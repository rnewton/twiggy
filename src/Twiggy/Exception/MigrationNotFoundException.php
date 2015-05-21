<?php

namespace Twiggy\Exception;

class MigrationNotFoundException extends \Exception
{
    public function __construct($id)
    {
        parent::__construct("No migration with ID '$id'.");
    }
}