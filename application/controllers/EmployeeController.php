<?php

require_once('./helpers/ResponseHelper.php');
require_once('./helpers/TokenHelper.php');
require_once('./helpers/RateLimiter.php');
require_once('./models/Employee.php');

class EmployeeController {
    private $masterDb;
    private $employeeModel;

    public function __construct($masterDb) {
        $this->masterDb = $masterDb;
        $this->employeeModel = new Employee($masterDb);
    }

    public function handleRequest($endpoint) {
        $postData = json_decode(file_get_contents('php://input'), true);
        $id = $postData['id'] ?? '';

        try {
            switch ($endpoint) {
                case 'getList':
                    $result = $this->employeeModel->getList();
                    break;
                case 'getStatus':
                    if (empty($id)) {
                        ResponseHelper::sendResponse(400, ['error' => 'NIC is required for getStatus']);
                    }
                    $result = $this->employeeModel->getStatus($id);
                    break;
                case 'getFullName':
                    if (empty($id)) {
                        ResponseHelper::sendResponse(400, ['error' => 'NIC is required for getFullName']);
                    }
                    $result = $this->employeeModel->getFullName($id);
                    break;
                
                default:
                    ResponseHelper::sendResponse(400, ['error' => 'Invalid Endpoint']);
            }
            ResponseHelper::sendResponse(200, $result);
        } catch (Exception $e) {
            ResponseHelper::sendResponse(500, ['error' => $e->getMessage()]);
        }
    }

    public function createEmployee() {
        $postData = json_decode(file_get_contents('php://input'), true);

        if (!isset($postData['fullName']) || !isset($postData['nic'])) {
            ResponseHelper::sendResponse(400, ['error' => 'Full name and NIC are required']);
        }

        $fullName = $postData['fullName'];
        $nic = $postData['nic'];

        try {
            $result = $this->employeeModel->create($fullName, $nic);
            ResponseHelper::sendResponse(201, ['message' => 'Employee created successfully', 'employee_id' => $result]);
        } catch (PDOException $e) {
            ResponseHelper::sendResponse(500, ['error' => 'Database error: ' . $e->getMessage()]);
        }
    }
}
