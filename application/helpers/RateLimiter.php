<?php

class RateLimiter {
    private $redis;
    private $maxRequestsPerMinute;
    private $blockTime;

    public function __construct($redis, $maxRequestsPerMinute = 20, $blockTime = 60) {
        $this->redis = $redis;
        $this->maxRequestsPerMinute = $maxRequestsPerMinute;
        $this->blockTime = $blockTime;
    }

    public function rateLimit($ipAddress) {
        $requestCountKey = 'api_request_' . $ipAddress;
        $blockKey = 'block_' . $ipAddress;

        if ($this->redis->exists($blockKey)) {
            ResponseHelper::sendResponse(429, ['error' => 'Rate limit exceeded. Try again later.']);
            return false;
        }

        $requestCount = (int) $this->redis->get($requestCountKey);

        if ($requestCount >= $this->maxRequestsPerMinute) {
            $this->redis->setex($blockKey, $this->blockTime, true);
            ResponseHelper::sendResponse(429, ['error' => 'Rate limit exceeded']);
            return false;
        }

        $this->redis->incr($requestCountKey);
        $this->redis->expire($requestCountKey, 60);

        return true;
    }
}
?>
