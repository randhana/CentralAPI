<?php

class Employee {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getList() {
        $sql = "SELECT em_name FROM api_employee";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStatus($nic) {
        $sql = "SELECT status FROM api_employee WHERE em_nic = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $nic);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    

    public function getFullName($nic) {
        $sql = "SELECT em_name AS FullName FROM api_employee WHERE em_nic = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $nic);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($fullName, $nic) {
        $sql = "INSERT INTO api_employee (em_name, em_nic) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $fullName);
        $stmt->bindParam(2, $nic);
        $stmt->execute();
        return $this->db->lastInsertId();
    }
}
