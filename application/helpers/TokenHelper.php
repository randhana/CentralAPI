<?php

require_once '../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class TokenHelper {
    private static $publicKeyPath = './public.pem'; //public key 

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
        global $log; // global log object

        try {
            $publicKey = file_get_contents(self::$publicKeyPath);
            $decoded = JWT::decode($token, new Key($publicKey, 'RS256'));
            $user = (array) $decoded;

            if (new DateTime() > new DateTime('@' . $user['exp'])) {
                $log->warning('Token has expired', ['token' => $token]);
                ResponseHelper::sendResponse(401, ['error' => 'Token has expired']);
                return false;
            }

            return $user;
        } catch (\Firebase\JWT\ExpiredException $e) {
            $log->warning('Token has expired', ['token' => $token]);
            ResponseHelper::sendResponse(401, ['error' => 'Token has expired']);
            return false;
        } catch (\Exception $e) {
            $log->warning('Invalid token', ['token' => $token, 'error' => $e->getMessage()]);
            ResponseHelper::sendResponse(401, ['error' => 'Invalid token: ' . $e->getMessage()]);
            return false;
        }
    }
}
?>
