<?php

namespace TraduireSansMigraine;

if (!defined("ABSPATH")) {
    exit;
}

class Cache
{
    static public $EXPIRATION = [
        "ONLY_MEMORY" => 0,
        "ONE_HOUR" => 3600,
        "ONE_DAY" => 86400,
        "MAX" => 86400 * 7
    ];
    static private $cache = [];
    private static $instances = [];
    private $className;

    public function __construct($instance)
    {
        $this->className = get_class($instance);
        self::$instances[$this->className] = $instance;
    }

    public static function getGlobalCache($className, $method, $args)
    {
        return self::$cache[$className][$method][serialize($args)] ?? null;
    }

    public static function getInstance($class)
    {
        if (self::$instances === null) {
            new Cache($class);
        }
        return self::$instances[get_class($class)];
    }

    public function getCache($method, $args = [])
    {
        $key = $this->getCacheKey($method, $args);
        return get_transient($key);
    }

    public function getCacheKey($method, $args)
    {
        return $this->className . $method . serialize($args);
    }

    public function setCache($method, $args, $value, $expiration = 3600)
    {
        $key = $this->getCacheKey($method, $args);
        if ($expiration > 0) {
            set_transient($key, $value, $expiration);
        }
        self::setGlobalCache($this->className, $method, $args, $value);
    }

    public static function setGlobalCache($className, $method, $args, $value)
    {
        if (!isset(self::$cache[$className])) {
            self::$cache[$className] = [];
        }
        if (!isset(self::$cache[$className][$method])) {
            self::$cache[$className][$method] = [];
        }
        self::$cache[$className][$method][serialize($args)] = $value;
    }

    public function clearCache()
    {
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_" . $this->className . "%'");
        self::deleteGlobalCache($this->className);
    }

    public static function deleteGlobalCache($className, $method = null, $args = null)
    {
        if ($method === null && isset(self::$cache[$className])) {
            unset(self::$cache[$className]);
            return;
        }
        if ($method !== null && $args === null && isset(self::$cache[$className][$method])) {
            unset(self::$cache[$className][$method]);
            return;
        }
        if ($method !== null && $args !== null && isset(self::$cache[$className][$method])) {
            unset(self::$cache[$className][$method][serialize($args)]);
        }
    }

    public function clearCacheByMethod($method)
    {
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_" . $this->className . $method . "%'");
        self::deleteGlobalCache($this->className, $method);
    }

    public function clearCacheByArgs($method, $args)
    {
        $key = $this->getCacheKey($method, $args);
        delete_transient($key);
        self::deleteGlobalCache($this->className, $method, $args);
    }
}