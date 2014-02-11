<?php
/**
 * @author sjoorm <sjoorm1@gmail.com>
 * date: 2014-01-31
 */
namespace base\components;
/**
 * Class CacheProvider is interface for caching
 * @package base\components
 */
class CacheProvider {
    /* @var Cache cache class name */
    private static $_cache;
    /** constants */
    const EXPIRE = 300;

    /**
     * Initialization procedure for cache service
     * @param array
     */
    public static function init($config) {
        //empty
        if(isset($config['class']) && class_exists($config['class'])) {
            $cache = $config['class'];
            /* @var Cache $cache */
            self::$_cache = new $cache($config);
        } else {
            self::$_cache = new Cache($config);
        }
    }

    /**
     * Gets value from cache
     * @param string $key
     * @param mixed $defaultValue returned if key does not exists
     * @return mixed
     */
    public static function get($key, $defaultValue = null) {
        return self::$_cache->get($key, $defaultValue);
    }

    /**
     * Sets value in cache, rewrites currect if already exists
     * @param string $key
     * @param mixed $value
     * @param integer $expire seconds to expire, 0 - without a limit
     * @return boolean
     */
    public static function set($key, $value, $expire = self::EXPIRE) {
        return self::$_cache->set($key, $value, $expire);
    }

    /**
     * Adds value in cache <b>only</b> if does not exists
     * @param string $key
     * @param mixed $value
     * @param integer $expire seconds to expire, 0 - without a limit
     * @return boolean
     */
    public static function add($key, $value, $expire = self::EXPIRE) {
        return self::$_cache->add($key, $value, $expire);
    }

    /**
     * Deletes value from cache
     * @param string $key
     * @return boolean
     */
    public static function delete($key) {
        return self::$_cache->delete($key);
    }
}
