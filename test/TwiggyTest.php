<?php

require_once('vendor/autoload.php');

use Twiggy\Twiggy;
use Twiggy\Configuration;


class TwiggyTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Twiggy\Twiggy
     */
    protected static $twiggy;

    /**
     * @var Migration
     */
    protected static $created;


    public static function setUpBeforeClass()
    {
        $config = new Configuration(json_decode(file_get_contents('test/test_config.json'), true));
        self::$twiggy = new Twiggy($config);
    }


    public static function tearDownAfterClass()
    {
        self::$twiggy = null;
    }


    /**
     * Tests that Twiggy was successfully installed using the test configuration (sqlite in memory).
     */
    public function testInstall()
    {
        // Technically, by instantiating a Twiggy instance we install the default migration. 
        // Getting to this point already means that it succeeded. 
        // We'll double check anyway...
        $migrations = self::$twiggy->getAll(['ran' => 'all']);
        $this->assertEquals(1, count($migrations));
    }


    /**
     * @depends testInstall
     * 
     * Tests creating a new migration
     */
    public function testCreate()
    {
        self::$created = self::$twiggy->create('Test migration', 'TEST-123', 'Some Whooper');

        $retrieved = self::$twiggy->get(self::$created->getId());
        $this->assertEquals(self::$created->getDescription(), $retrieved->getDescription());
        $this->assertEquals(self::$created->getTicket(), $retrieved->getTicket());
        $this->assertEquals(self::$created->getAuthor(), $retrieved->getAuthor());
    }


    /**
     * @depends testCreate
     * 
     * Tests applying migraton changes
     */
    public function testApply()
    {
        self::$twiggy->apply(self::$created);
    }


    /**
     * @depends testApply
     * 
     * Tests rolling back migraton changes
     */
    public function testRollback()
    {
        self::$twiggy->rollback(self::$created);
    }


    /**
     * @depends testRollback
     * 
     * Tests removing a migration
     */
    public function testRemove()
    {
        self::$twiggy->remove(self::$created);
    }
}