<?php
// app/RateLimiter.php

class RateLimiter {
    private $redis;
    private $ip;

    public function __construct($redisClient, $ip) {
        $this->redis = $redisClient;
        $this->ip = $ip;
    }

    public function allow($action, $maxRequests = 60, $windowSeconds = 60) {
        if (!$this->redis || !$this->redis->isEnabled()) {
            // If Redis is not available, bypass rate limiting (graceful fallback)
            return true;
        }

        $key = "ratelimit:{$action}:{$this->ip}";
        
        $current = $this->redis->incr($key, $windowSeconds);

        // If Redis is down (incr returns false), fail open to avoid breaking app
        if ($current === false) {
            return true;
        }

        if ($current > $maxRequests) {
            return false;
        }

        return true; 
    }
}
