<?php
/**
 * @author sjoorm <sjoorm1@gmail.com>
 * date: 2014-02-01
 */
namespace leprechaun;
use base\components\Cache;
/**
 * Class Redis
 * @package ats
 */
class Redis extends Cache {
    /* @var \Redis redis connection */
    protected static $_connection;
    /** constants */
    const DEFAULT_PORT = 6379;

    /**
     * @param array $config
     */
    public function __construct($config) {
        if(!static::$_connection) {
            static::$_connection = new \Redis();
            static::$_connection->connect(
                isset($config['server']) ? $config['server'] : static::DEFAULT_SERVER,
                isset($config['port']) ? $config['port'] : static::DEFAULT_PORT
            );
        }
    }

    public function set($key, $value, $expire = 0) {
        return static::$_connection->set($key, serialize($value), $expire);
    }

    public function get($key, $defaultValue = null) {
        $result = unserialize(static::$_connection->get($key));
        return $result ? $result : $defaultValue;
    }

    public function delete($key) {
        return static::$_connection->del($key);
    }

    public function add($key, $value, $expire = 0) {
        return static::$_connection->set($key, $value, $expire);
    }
}
