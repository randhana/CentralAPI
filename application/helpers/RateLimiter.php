<?php

class RateLimiter {
    public static function rateLimit($ipAddress, $maxRequestsPerMinute) {
        global $redis;

        $requestCountKey = 'api_request_' . $ipAddress;
        $requestCount = (int) $redis->get($requestCountKey);

        if ($requestCount >= $maxRequestsPerMinute) {
            ResponseHelper::sendResponse(429, ['error' => 'Rate limit exceeded']);
            return false;
        }

        $redis->incr($requestCountKey);
        $redis->expire($requestCountKey, 60);
        
        return true;
    }
}
?>
