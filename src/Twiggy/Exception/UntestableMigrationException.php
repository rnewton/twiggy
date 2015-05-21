<?php

namespace Twiggy\Exception;

class UntestableMigrationException extends \Exception
{
    public function __construct($id)
    {
        parent::__construct("Migration $id is not testable.");
    }
}