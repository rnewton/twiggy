<?php

namespace Twiggy;

use \Twiggy\Exception\MissingConfigurationKeyException;

class Configuration implements \ArrayAccess
{
    /**
     * @var mixed[]
     */
    private $container = [];

    const DATABASE_DSN = 'db_dsn';
    const DATABASE_USER = 'db_user';
    const DATABASE_PASSWORD = 'db_password';
    const DATABASE_OPTIONS = 'db_options';

    const MIGRATION_TABLE = 'tableName';
    const MIGRATION_DIRECTORY = 'directory';
    const MIGRATION_ID_FORMAT = 'idFormat';

    public static $requiredKeys = [
        self::DATABASE_DSN,
        self::MIGRATION_TABLE,
        self::MIGRATION_DIRECTORY,
        self::MIGRATION_ID_FORMAT
    ];

    private static $defaultValues = [
        self::DATABASE_USER => null,
        self::DATABASE_PASSWORD => null,
        self::DATABASE_OPTIONS => null,
        self::MIGRATION_TABLE => 'migrations',
        self::MIGRATION_DIRECTORY => 'migrations',
        self::MIGRATION_ID_FORMAT => '/\d{8}_\d{6}/'
    ];


    public function __construct(array $data)
    {
        // Set internal values
        $this->container = array_merge(self::$defaultValues, $data);

        // Check that we have the required keys
        foreach (self::$requiredKeys as $required) {
            if (!isset($this->container[$required])) {
                throw new MissingConfigurationKeyException($required);
            }
        }        
    }


    /**
     * Check if the given offset/key exists.
     * 
     * @param  mixed  $offset 
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }


    /**
     * Returns the value at the given offset/key.
     * 
     * @param  mixed  $offset 
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->container[$offset] : null;
    }


    /**
     * Sets the offset/key and value pair.
     * 
     * @param  mixed  $offset
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }


    /**
     * Removes the value at the given offset/key.
     * 
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }
}