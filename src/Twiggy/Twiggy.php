<?php

namespace Twiggy;

use \Twiggy\Exception\MissingMigrationDirectoryException;
use \Twiggy\Exception\UntestableMigrationException;
use \Twiggy\Exception\MissingMigrationException;
use \Twiggy\Exception\UnmetDependencyException;
use \Twiggy\Exception\MissingMigrationTableException;

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
     * @var Configuration
     */
    private $config;

    /**
     * @var Migration[]
     */
    private $migrations = [];


    /**
     * Creates the Twiggy Migration manager.
     * 
     * @param Configuration $config 
     */
    public function __construct(Configuration $config)
    {
        $this->db = new Connection(
            $config[Configuration::DATABASE_DSN], 
            $config[Configuration::DATABASE_USER],
            $config[Configuration::DATABASE_PASSWORD], 
            $config[Configuration::DATABASE_OPTIONS]
        );

        $this->config = $config;

        // Make sure the database is setup for twiggy
        try {
            $this->checkTwiggySetup();
        } catch (MissingMigrationTableException $e) {
            $this->setup();
        }

        // Scan for migrations in local filesystem and connected database
        $this->loadFromDatabase();
        $this->loadFromFiles();
    }


    /**
     * Sets up Twiggy's database so that we can use it normally. 
     *
     */
    private function setup()
    {
        $rootMigration = $this->loadFromFile('00000000_000000');
        $this->apply($rootMigration);
    }


    /**
     * Loads migrations from the database records.
     *
     */
    private function loadFromDatabase()
    {
        $results = $this->db->query('SELECT id FROM ' . $this->config[Configuration::MIGRATION_TABLE]);
        foreach ($results as $row) {
            $this->migrations[$row['id']] = $this->loadFromFile($row['id']);
        }
    }


    /**
     * Loads migrations from the filesystem.
     *
     */
    private function loadFromFiles()
    {
        $fs = new Filesystem();
        $path = $this->config[Configuration::MIGRATION_DIRECTORY];

        if (!$fs->exists($path)) {
            throw new MissingMigrationDirectoryException();
        }

        foreach (new \DirectoryIterator($path) as $file) {
            if (!$file->isDot() && preg_match($this->config[Configuration::MIGRATION_ID_FORMAT], $file->getFilename(), $matches)) {
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
    private function loadFromFile($id)
    {
        $fs = new Filesystem();
        $fileinfo = $this->getMigrationFileInfo($id);

        if (!$fs->exists($fileinfo['filepath'])) {
            $migration = new MissingMigration($this->db, $id);
        } else {
            require_once($fileinfo['filepath']);

            $migration = new $fileinfo['classname']($this->db, $id);
        }

        return $migration;
    }

    /**
     * Returns all migrations matching the specified parameters.
     *
     * @param string $params
     * @return Migration[]
     */
    public function getAll(array $params)
    {
        // TODO
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
            $migration->commitTransaction();
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

        $this->unmark($migration);
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
        $date = new \DateTime();
        $runDate = $date->format('Y-m-d H:i:s');

        // Update the database
        $this->db->query(
            "UPDATE {$this->config[Configuration::MIGRATION_TABLE]} SET run_date = ? WHERE id = ?", 
            $runDate,
            $migration->getId()
        );

        $migration->setRunDate($runDate);
    }


    /**
     * Unmarks a migration as run without actually rolling it back.
     * 
     * @param  Migration $migration
     */
    public function unmark(Migration $migration)
    {
        // Update the database
        $this->db->query(
            "UPDATE {$this->config[Configuration::MIGRATION_TABLE]} SET run_date = NULL WHERE id = ?", 
            $migration->getId()
        );

        $migration->setRunDate(null);
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
        $date = new \DateTime();
        $id = $date->format('Ymd_His');

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
        $path = $this->config[Configuration::MIGRATION_DIRECTORY];

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
        foreach ($migration->getDependencies() as $dependency) {
            if (!isset($this->migrations[$dependency])) {
                throw new MissingMigrationException();
            }

            if (!$this->migrations[$dependency]->isApplied()) {
                throw new UnmetDependencyException($dependency);
            }
        }
    }


    /**
     * Checks that twiggy has been setup for use.
     *
     * @throws MissingMigrationTableException
     */
    private function checkTwiggySetup()
    {
        $tables = $this->db->getSupplementalDriver()->getTables();
        if (!in_array($this->config[Configuration::MIGRATION_TABLE], $tables)) {
            throw new MissingMigrationTableException();
        }
    }
}