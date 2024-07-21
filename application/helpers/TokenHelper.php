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

    public static function verifyToken($apiDb, $token) {
        if (!self::$logger) {
            throw new \Exception('Logger is not initialized.');
        }
        try {
            //JWT Verification
            $publicKey = file_get_contents(self::$publicKeyPath);
            $decoded = JWT::decode($token, new Key($publicKey, 'RS256'));
            $user = (array) $decoded;

            //Database Lookup
            $userModel = new User($apiDb);
            $dbUser = $userModel->getUserByToken($token);

            if (!$dbUser) {
                return 'invalid';
            }
            // Check token expiry
            if (new DateTime($dbUser['token_expiry']) < new DateTime()) {
                return 'expired';
            }

            return $user;

        } catch (\Firebase\JWT\ExpiredException $e) {
            return 'expired';

        } catch (\Exception $e) {
            return 'invalid';

        } catch (PDOException $e) {
            self::$logger->error('Token verification failed', ['error' => $e->getMessage()]);
            ResponseHelper::sendResponse(500, ['error' => 'Token verification failed: ' ]);
        }
    }
    
}
?>
