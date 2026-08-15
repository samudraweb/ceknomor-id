<?php
// config/redis.example.php
// Copy this file to redis.php

class RedisClient {
    private $redis = null;
    private $enabled = false;

    public function __construct() {
        if (class_exists('Redis')) {
            try {
                $this->redis = new Redis();
                // Standard default localhost:6379, 1 sec timeout
                $connected = @$this->redis->connect('127.0.0.1', 6379, 1.0);
                if ($connected) {
                    $this->enabled = true;
                }
            } catch (Exception $e) {
                // Redis is down or not running, gracefully degrade
                $this->enabled = false;
                error_log("Redis connection failed: " . $e->getMessage());
            }
        }
    }

    public function get($key) {
        if (!$this->enabled) return false;
        return $this->redis->get($key);
    }

    public function set($key, $value, $ttl = 3600) {
        if (!$this->enabled) return false;
        return $this->redis->setex($key, $ttl, $value);
    }
    
    public function incr($key) {
        if (!$this->enabled) return false;
        return $this->redis->incr($key);
    }

    public function expire($key, $ttl) {
        if (!$this->enabled) return false;
        return $this->redis->expire($key, $ttl);
    }
}
