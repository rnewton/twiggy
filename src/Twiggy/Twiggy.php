<?php

namespace Twiggy;

use Twiggy\Exception\MissingMigrationDirectoryException;
use Twiggy\Exception\UntestableMigrationException;
use Twiggy\Exception\MissingMigrationException;
use Twiggy\Exception\UnmetDependencyException;
use Twiggy\Exception\MissingMigrationTableException;
use Twiggy\Exception\MigrationNotFoundException;

use Nette\Database\Connection;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;


class Twiggy
{
    /**
     * @var Nette\Database\Connection
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
        $results = $this->db->query("SELECT id, run_date FROM {$this->config[Configuration::MIGRATION_TABLE]} ORDER BY id DESC");
        foreach ($results as $row) {
            $this->migrations[$row['id']] = $this->loadFromFile($row['id']);
            $this->migrations[$row['id']]->setRunDate($row['run_date']);
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
            throw new MissingMigrationDirectoryException($path);
        }

        foreach (new \DirectoryIterator($path) as $file) {
            // Grab all files that match the ID format
            if (
                !$file->isDot() && 
                preg_match($this->config[Configuration::MIGRATION_ID_FORMAT], $file->getFilename(), $matches)
            ) {
                // Check if anything unknown to the database shows up
                if (!isset($this->migrations[$matches[0]])) {
                    // Create database record
                    $this->db->query("INSERT INTO {$this->config[Configuration::MIGRATION_TABLE]} (id) VALUES (?)", $matches[0]);

                    // Load
                    $classname = 'Migration_' . $matches[0];
                    require_once($file->getPathname());
                    $this->migrations[$matches[0]] = new $classname($this->db, $matches[0]);
                }
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
     * Returns a single migration, by ID. 
     * 
     * @param  string $id
     * @return Migration
     * @throws MigrationNotFoundException
     */
    public function get($id)
    {
        if (isset($this->migrations[$id])) {
            return $this->migrations[$id];
        }

        throw new MigrationNotFoundException($id);
    }


    /**
     * Returns all migrations matching the specified parameters.
     *
     * @param string $params
     * @return Migration[]
     */
    public function getAll(array $params)
    {
        $resultSet = $this->migrations;

        // Search on id
        if (isset($params['id'])) {
            $resultSet = $this->filterId($resultSet, $params['id']);
        }

        // Search on ticket
        if (isset($params['ticket'])) {
            $resultSet = $this->filterTicket($resultSet, $params['ticket']);
        }

        // Search on author
        if (isset($params['author'])) {
            $resultSet = $this->filterAuthor($resultSet, $params['author']);
        }

        // Filter by run date
        if (isset($params['ran'])) {
            $resultSet = $this->filterRan($resultSet, $params['ran']);
        }

        // Search on description
        if (isset($params['description'])) {
            $resultSet = $this->filterDescription($resultSet, $params['description']);
        }

        return $resultSet;
    }


    /**
     * Filters the given set of migrations by ID with the given value.
     * 
     * @param  Migration[] $migrations
     * @param  string      $filterValue
     * @return Migration[]
     */
    private function filterId($migrations, $filterValue)
    {
        foreach ($migrations as $index => &$migration) {
            if (false === (strpos($migration->getId(), $filterValue))) {
                unset($migrations[$index]);
            }
        }

        return $migrations;
    }


    /**
     * Filters the given set of migrations by ticket with the given value.
     * 
     * @param  Migration[] $migrations
     * @param  string      $filterValue
     * @return Migration[]
     */
    private function filterTicket($migrations, $filterValue)
    {
        foreach ($migrations as $index => &$migration) {
            if (false === (strpos($migration->getTicket(), $filterValue))) {
                unset($migrations[$index]);
            }
        }

        return $migrations;
    }


    /**
     * Filters the given set of migrations by author with the given value.
     * 
     * @param  Migration[] $migrations
     * @param  string      $filterValue
     * @return Migration[]
     */
    private function filterAuthor($migrations, $filterValue)
    {
        foreach ($migrations as $index => &$migration) {
            if (false === (strpos($migration->getAuthor(), $filterValue))) {
                unset($migrations[$index]);
            }
        }

        return $migrations;
    }


    /**
     * Filters the given set of migrations by run date. Options are "ran", "unran" and "all".
     * 
     * @param  Migration[] $migrations
     * @param  string      $filterValue
     * @return Migration[]
     */
    private function filterRan($migrations, $filterValue)
    {
        if ('all' == $filterValue) {
            return $migrations; // Nothing to do
        }

        foreach ($migrations as $index => &$migration) {
            if ('unran' == $filterValue && $migration->isApplied()) {
                unset($migrations[$index]);
            } else if ('ran' == $filterValue && !$migration->isApplied()) {
                unset($migrations[$index]);
            }
        }

        return $migrations;
    }


    /**
     * Filters the given set of migrations by description with the given value.
     * 
     * @param  Migration[] $migrations
     * @param  string      $filterValue
     * @return Migration[]
     */
    private function filterDescription($migrations, $filterValue)
    {
        foreach ($migrations as $index => &$migration) {
            if (false === (strpos($migration->getDescription(), $filterValue))) {
                unset($migrations[$index]);
            }
        }

        return $migrations;
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
            $migration->commitTransaction();
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
            throw new UntestableMigrationException($migration->getId());
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
    private function unmark(Migration $migration)
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
     * @return Migration
     */
    public function create($description = 'Data migration', $ticket = '', $author = '')
    {
        $date = new \DateTime();
        $id = $date->format('Ymd_His');

        // Create database entry
        $this->db->query("INSERT INTO {$this->config[Configuration::MIGRATION_TABLE]} (id) VALUES (?)", $id);

        // Create migration file
        $fileinfo = $this->getMigrationFileInfo($id);
        $template = file_get_contents($this->config[Configuration::MIGRATION_DIRECTORY] . '/Migration.template');
        $migration = str_replace(['%id%', '%description%', '%author%', '%ticket%'], [$id, $description, $author, $ticket], $template);

        // Load new migration
        file_put_contents($fileinfo['filepath'], $migration);
        $this->migrations[$id] = $this->loadFromFile($id);

        return $this->migrations[$id];
    }


    /**
     * Removes a migration entirely (and rolls it back).
     * 
     * @param  Migration $migration
     */
    public function remove(Migration $migration, $rollback = true)
    {
        if ($migration->isApplied() && $rollback) {
            $this->rollback($migration);
        }

        $fileinfo = $this->getMigrationFileInfo($migration->getId());

        // Delete the file
        if (!is_a($migration, 'Twiggy\MissingMigration')) {
            unlink($fileinfo['filepath']);
        }

        // Delete the database record
        $this->db->query("DELETE FROM {$this->config[Configuration::MIGRATION_TABLE]} WHERE id = ?", $migration->getId());

        // Remove the object from memory
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
            throw new MissingMigrationDirectoryException($path);
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
        foreach ($tables as $table) {
            if ($this->config[Configuration::MIGRATION_TABLE] == $table['name']) {
                return;
            }
        }

        throw new MissingMigrationTableException();
    }
}