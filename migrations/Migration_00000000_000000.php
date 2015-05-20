<?php

class Migration_00000000_000000 extends \Twiggy\Migration
{
    protected $description = 'Root migration for setting up Twiggy';
    protected $author = 'rnewton';
    protected $ticket = '';
    protected $transactional = true;
    protected $dependencies = [];


    public function apply()
    {
        // Create the migration table
        $this->db->query('
            CREATE TABLE migrations (
                id        TEXT      NOT NULL    PRIMARY KEY, 
                run_date  TIMESTAMP             DEFAULT NULL
            )
        ');
    }


    public function rollback()
    {
        $this->db->query('DROP TABLE migrations');

        // Also a special case since we're removing the table we'd normally update with the null run_date
        exit(0);
    }
}