<?php

namespace Twiggy;

use \Nette\Database\Connection;
use \Symfony\Component\Filesystem\Filesystem;
use \Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class Twiggy
{
    /**
     * @var \Nette\Database\Connection
     */
    private $db;

    /**
     * @var TwiggyConfiguration
     */
    private $config;

    /**
     * @var Migration[]
     */
    private $migrations = [];


    /**
     * Creates the Twiggy Migration manager.
     * 
     * @param TwiggyConfiguration $config 
     */
    public function __construct(TwiggyConfiguration $config)
    {
        $this->db = new Connection(
            $config[TwiggyConfiguration::DATABASE_DSN], 
            $config[TwiggyConfiguration::DATABASE_USER],
            $config[TwiggyConfiguration::DATABASE_PASSWORD], 
            $config[TwiggyConfiguration::DATABASE_OPTIONS]
        );

        $this->config = $config;

        // Scan for migrations in local filesystem and connected database
        $this->loadFromDatabase();
        $this->loadFromFiles();
    }


    /**
     * Loads migrations from the database records.
     *
     */
    public function loadFromDatabase()
    {
        $results = $this->db->query('SELECT id FROM ?', [$this->config[TwiggyConfiguration::MIGRATION_TABLE_KEY]]);
        foreach ($results as $row) {
            $this->migrations[$row['id']] = $this->loadFromFile($row['id']);
        }
    }


    /**
     * Loads migrations from the filesystem.
     *
     */
    public function loadFromFiles()
    {
        $fs = new Filesystem();
        $path = $this->config[TwiggyConfiguration::MIGRATION_DIRECTORY];

        if (!$fs->exists($path)) {
            throw new MissingMigrationDirectoryException();
        }

        foreach (new \DirectoryIterator($path) as $file) {
            if (!$file->isDot() && preg_match($this->config[TwiggyConfiguration::MIGRATION_ID_FORMAT, $file->getFilename(), $matches)) {
                $classname = 'Migration_' . $matches[0];

                require_once($file->getFilename());
                $this->migrations[$matches[0]] = new $classname($this->db, $matches[0]);
            }
        }
    }
    

    /**
     * Loads an individual migration from a file. 
     * 
     * @param  string $id
     * @return Migration
     */
    public function loadFromFile($id)
    {
        $fileinfo = $this->getMigrationFileInfo($id);

        if (!$fs->exists($fileinfo['filepath'])) {
            $migration = new MissingMigration($this->db, $id);
        } else {
            require_once($fileinfo['filepath']);

            $migration = new $classname($this->db, $id);
        }

        return $migration;
    }

    /**
     * Returns the list of migrations
     * @return Migration[]
     */
    public function list()
    {
        return $this->migrations;
    }


    /**
     * Applies a migration.
     * 
     * @param  Migration $migration
     */
    public function apply(Migration $migration)
    {
        if ($migration->isTransactional()) {
            $migration->beginTransaction();
        }

        $this->checkMigrationDependencies($migration);
        $migration->apply();

        if ($migration->isTransactional()) {
            $migration->commit();
        }

        $this->mark($migration);
    }


    /**
     * Rolls back a migration.
     * 
     * @param  Migration $migration
     */
    public function rollback(Migration $migration)
    {
        if ($migration->isTransactional()) {
            $migration->beginTransaction();
        }

        $migration->rollback();

        if ($migration->isTransactional()) {
            $migration->commit();
        }
    }


    /**
     * Tests a migration without applying it.
     * 
     * @param  Migration $migration
     */
    public function test(Migration $migration)
    {
        if ($migration->isTransactional()) {
            $migration->beginTransaction();
        } else {
            throw new UntestableMigrationException($migration);
        }

        $this->checkMigrationDependencies($migration);
        $migration->apply();

        // No commit, no changes saved
    }


    /**
     * Marks a migration as run without actually running it.
     * 
     * @param  Migration $migration
     */
    public function mark(Migration $migration)
    {
        $migration->setRunDate(new DateTime());
    }


    /**
     * Creates a new migration.
     * 
     * @param  string $description
     * @param  string $author
     * @param  string $ticket
     */
    public function create($description = 'Data migration', $author = '', $ticket = '')
    {
        $id = new \DateTime()->format('Ymd_His');

        $fileinfo = $this->getMigrationFileInfo($id);

        $template = file_get_contents('./Migration.template');
        $migration = str_replace(['%id%', '%description%', '%author%', '%ticket%'], [$id, $description, $author, $ticket], $template);

        file_put_contents($fileinfo['filepath'], $migration);

        require_once($fileinfo['filepath']);
        $this->migrations[$id] = new $classname($this->db, $id);
    }


    /**
     * Removes a migration entirely (and rolls it back).
     * 
     * @param  Migration $migration
     */
    public function remove(Migration $migration)
    {
        if ($migration->isApplied()) {
            $this->rollback($migration);
        }

        $fileinfo = $this->getMigrationFileInfo($migration->getId());

        unlink($fileinfo['filepath']);
        unset($this->migrations[$migration->getId()]);
    }


    /**
     * Returns generic file info for a hypothetical migration with the given id.
     * 
     * @param  string $id
     * @return string[]
     */
    private function getMigrationFileInfo($id)
    {
        $fs = new Filesystem();
        $path = $this->config[TwiggyConfiguration::MIGRATION_DIRECTORY];

        if (!$fs->exists($path)) {
            throw new MissingMigrationDirectoryException();
        }

        $classname = "Migration_$id";
        $filename = $classname . '.php';
        $filepath = $path . '/' . $filename;

        return [
            'classname' => $classname, 
            'filename' => $filename, 
            'filepath' => $filepath
        ];
    }


    /**
     * Checks that a given migration's dependencies have been met.
     *
     * @throws MissingMigrationException
     * @throws UnmetDependencyException
     * @param  Migration $migration
     */
    private function checkMigrationDependencies(Migration $migration)
    {
        foreach ($migration->dependencies as $dependency) {
            if (!isset($this->migrations[$dependency])) {
                throw new MissingMigrationException();
            }

            if (!$this->migrations[$dependency]->isApplied()) {
                throw new UnmetDependencyException($dependency);
            }
        }
    }
}