<?php
/**
 * @author sjoorm <sjoorm1@gmail.com>
 * date: 2014-01-30
 */
namespace base\components;
/**
 * Class Cache default cache proto component.
 * You need to implement your own for specific cache service
 * @package base\components
 */
class Cache {
    /* @var Cache cache connection instance */
    protected static $_connection;
    /** constants */
    const DEFAULT_SERVER = 'localhost';
    const DEFAULT_PORT = 11211;

    /**
     * Initialization procedure for cache service
     * @param array
     */
    public function __construct($config) {
        //no cache
    }

    /**
     * Gets value from cache
     * @param string $key
     * @param mixed $defaultValue returned if key does not exists
     * @return mixed
     */
    public function get($key, $defaultValue = null) {
        return $defaultValue;
    }

    /**
     * Sets value in cache, rewrites currect if already exists
     * @param string $key
     * @param mixed $value
     * @param integer $expire seconds to expire, 0 - without a limit
     * @return boolean
     */
    public function set($key, $value, $expire = 0) {
        return true;
    }

    /**
     * Adds value in cache <b>only</b> if does not exists
     * @param string $key
     * @param mixed $value
     * @param integer $expire seconds to expire, 0 - without a limit
     * @return boolean
     */
    public function add($key, $value, $expire = 0) {
        return false;
    }

    /**
     * Deletes value from cache
     * @param string $key
     * @return boolean
     */
    public function delete($key) {
        return true;
    }
}
