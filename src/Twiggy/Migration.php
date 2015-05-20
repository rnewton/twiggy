<?php 

namespace Twiggy;

use \Twiggy\Exception\IrreversibleMigrationException;

use \Nette\Database\Connection;

abstract class Migration
{
    /**
     * @var \Nette\Database\Connection
     */
    protected $db;

    /**
     * Version number acts as the unique identifier for this migration
     * @var string
     */
    protected $id;

    /**
     * What this migraiton does
     * @var string
     */
    protected $description;

    /**
     * User responsible
     * @var string
     */
    protected $author;

    /**
     * Linking to ticket/case/card number
     * @var string
     */
    protected $ticket;

    /**
     * Whether or not this migration should be ran using a transaction
     * @var bool
     */
    protected $transactional;

    /**
     * Other migrations that must be run before this one. 
     * @var string[]
     */
    protected $dependencies = [];

    /**
     * Timestamp for when this migration was run.
     * @var DateTime|null
     */
    protected $runDate;


    public function __construct(Connection $db, $id, $description = '', $author = '', $ticket = '', $transactional = true, array $dependencies = array(), $runDate = null)
    {
        $this->db = $db;
        $this->id = $id;

        // Set defaults
        $this->setDescription($description);
        $this->setAuthor($author);
        $this->setTicket($ticket); 
        $this->setTransactional($transactional);
        $this->setDependencies($dependencies);
        $this->setRunDate($runDate);
    }


    /**
     * Apply database changes
     */
    public abstract function apply();


    /**
     * Undo database changes
     */
    public abstract function rollback();


    /**
     * For transactional migrations, initiate the transaction
     */
    public function beginTransaction()
    {
        if (!$this->isTransactional()) {
            return;
        }

        $this->db->beginTransaction();
    }


    /**
     * For transactional migrations, commit the changes
     */
    public function commitTransaction()
    {
        if (!$this->isTransactional()) {
            return;
        }

        $this->db->commit();
    }


    /**
     * Convenience function to throw an exception when a migration cannot be rolled back.
     * 
     * @param  string $reason
     * @throws IrreversibleMigrationException
     */
    protected function irreversible($reason = null)
    {
        throw new IrreversibleMigrationException($reason);
    }


    /**
     * @param \Nette\Database\Connection $db
     */
    private function setDb(Connection $db)
    {
        $this->db = $db;
    }


    /**
     * @return \Nette\Database\Connection
     */
    public function getDb()
    {
        return $this->db;
    }


    /**
     * @param string $id
     */
    private function setId($id)
    {
        $this->id = $id;
    }


    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * @param string $description
     */
    private function setDescription($description)
    {
        $this->description = $description;
    }


    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }


    /**
     * @param string $author
     */
    private function setAuthor($author)
    {
        $this->author = $author;
    }


    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }


    /**
     * @param string $ticket
     */
    private function setTicket($ticket)
    {
        $this->ticket = $ticket;
    }


    /**
     * @return string
     */
    public function getTicket()
    {
        return $this->ticket;
    }


    /**
     * @param bool $transactional
     */
    private function setTransactional($transactional)
    {
        $this->transactional = $transactional;
    }


    /**
     * @return bool
     */
    public function isTransactional()
    {
        return $this->transactional;
    }


    /**
     * @param string[] $dependecies
     */
    private function setDependencies(array $dependencies)
    {
        $this->dependencies = $dependencies;
    }


    /**
     * @return string[]
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }


    /**
     * @param DateTime|null $runDate
     */
    public function setRunDate($runDate)
    {
        // We don't want to end up with "now" if the migration is unran
        if (!is_string($runDate)) {
            $this->runDate = new \DateTime($runDate);
        } else {
            $this->runDate = $runDate;
        }
    }


    /**
     * @return DateTime|null
     */
    public function getRunDate()
    {
        return $this->runDate;
    }


    /**
     * @return bool
     */
    public function isApplied()
    {
        return is_null($this->runDate);
    }
}