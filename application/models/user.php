<?php

class User {
    private $apiDb;
    private static $logger;

    public function __construct($apiDb) {
        $this->apiDb = $apiDb;
    }

    public static function initializeLogger($logger) {
        self::$logger = $logger;
    }

    public function getUserByUsername($username) {
        $sql = "SELECT id, password FROM login WHERE username = ?";
        $stmt = $this->apiDb->prepare($sql);
        $stmt->bindParam(1, $username);
        try {
            if (!$stmt->execute()) {
                throw new Exception('Query failed: ' . implode(' ', $stmt->errorInfo()));
            }
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            self::$logger->error('Failed to get user by username', [
                'username' => $username,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e; 
        }
    }

    public function updateToken($id, $token, $expiry) {
        $sql = "UPDATE login SET token = ?, token_expiry = ? WHERE id = ?";
        $stmt = $this->apiDb->prepare($sql);
        $stmt->bindParam(1, $token);
        $stmt->bindParam(2, $expiry);
        $stmt->bindParam(3, $id);
        try {
            if (!$stmt->execute()) {
                throw new Exception('Query failed: ' . implode(' ', $stmt->errorInfo()));
            }
            return true;
        } catch (Exception $e) {
            self::$logger->error('Failed to update token', [
                'id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e; 
        }
    }
}
?>
