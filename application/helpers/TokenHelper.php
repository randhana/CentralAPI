<?php

class TokenHelper {
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
        try {
            $sql = "SELECT id, token_expiry FROM login WHERE token = ?";
            $stmt = $apiDb->prepare($sql);
            $stmt->bindParam(1, $token);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                ResponseHelper::sendResponse(401, ['error' => 'Invalid token']);
            }

            if (new DateTime($user['token_expiry']) < new DateTime()) {
                ResponseHelper::sendResponse(401, ['error' => 'Token has expired']);
            }

            return $user;
        } catch (PDOException $e) {
            ResponseHelper::sendResponse(500, ['error' => 'Token verification failed: ' . $e->getMessage()]);
        }
    }
}
?>
