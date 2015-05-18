<?php

namespace Twiggy

use \Twiggy\Exception;

class MissingMigration extends Migration
{
    protected $description = 'Missing migration file.';


    public function apply()
    {
        throw new MissingMigrationException();
    }


    public function rollback()
    {
        throw new MissingMigrationException();
    }
}