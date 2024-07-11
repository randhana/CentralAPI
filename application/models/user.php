<?php

class User {
    private $apiDb;

    public function __construct($apiDb) {
        $this->apiDb = $apiDb;
    }

    public function getUserByUsername($username) {
        $sql = "SELECT id, password FROM login WHERE username = ?";
        $stmt = $this->apiDb->prepare($sql);
        $stmt->bindParam(1, $username);
        if (!$stmt->execute()) {
            throw new Exception('Query failed: ' . implode(' ', $stmt->errorInfo()));
        }
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateToken($id, $token, $expiry) {
        $sql = "UPDATE login SET token = ?, token_expiry = ? WHERE id = ?";
        $stmt = $this->apiDb->prepare($sql);
        $stmt->bindParam(1, $token);
        $stmt->bindParam(2, $expiry);
        $stmt->bindParam(3, $id);
        if (!$stmt->execute()) {
            throw new Exception('Query failed: ' . implode(' ', $stmt->errorInfo()));
        }
        return true;
    }
}
