<?php

require_once '../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class TokenHelper {
    private static $publicKeyPath = './public.pem'; // public key
    private static $logger;

    public static function initializeLogger($logger) {
        self::$logger = $logger;
    }

    public static function getBearerToken() {
        $headers = apache_request_headers();
        if (!isset($headers['Authorization'])) {
            return null;
        }
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
        return null;
    }

    public static function verifyToken($token) {
        if (!self::$logger) {
            throw new \Exception('Logger is not initialized.');
        }
    
        try {
            $publicKey = file_get_contents(self::$publicKeyPath);
            $decoded = JWT::decode($token, new Key($publicKey, 'RS256'));
            $user = (array) $decoded;
    
            return $user;
        } catch (\Firebase\JWT\ExpiredException $e) {
            //self::$logger->warning('Token has expired', ['token' => $token, 'error' => $e->getMessage()]);
            return 'expired';

        } catch (\Exception $e) {
            //self::$logger->warning('Invalid token', ['token' => $token, 'error' => $e->getMessage()]);
            return 'invalid';
        }
    }
    
}
?>
