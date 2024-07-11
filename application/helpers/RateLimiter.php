<?php

class RateLimiter {
    public static function rateLimit($ipAddress, $maxRequestsPerMinute) {
        global $redis;

        $requestCountKey = 'request_count_' . $ipAddress;
        $requestCount = (int) $redis->get($requestCountKey);

        if ($requestCount >= $maxRequestsPerMinute) {
            ResponseHelper::sendResponse(429, ['error' => 'Rate limit exceeded']);
        }

        $redis->incr($requestCountKey);
        $redis->expire($requestCountKey, 60);
    }
}
?>
