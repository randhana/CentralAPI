<?php

require_once('./helpers/ResponseHelper.php');
require_once('./helpers/TokenHelper.php');
require_once('./helpers/RateLimiter.php');
require_once('./models/Employee.php');

class EmployeeController {
    private $employeeModel;

    public function __construct($masterDb) {
        $this->employeeModel = new Employee($masterDb);
    }

    public function handleGETRequest($endpoint) {
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
                    return; // Return to avoid sending additional response
            }
            ResponseHelper::sendResponse(200, $result);
        } catch (Exception $e) {
            ResponseHelper::sendResponse(500, ['error' => $e->getMessage()]);
        }
    }

    public function handlePOSTRequest($endpoint) {
        $postData = json_decode(file_get_contents('php://input'), true);

        try {
            switch ($endpoint) {
                case 'createEmployee':
                    $this->createEmployee($postData);
                    break;
                case 'uploadFile':
                    $this->uploadFile();
                    return; 
                default:
                    ResponseHelper::sendResponse(400, ['error' => 'Invalid Endpoint']);
                    return; 
            }
        } catch (Exception $e) {
            ResponseHelper::sendResponse(500, ['error' => $e->getMessage()]);
        }
    }

    private function createEmployee($postData) {
        if (!isset($postData['fullName']) || !isset($postData['nic'])) {
            ResponseHelper::sendResponse(400, ['error' => 'Full name and NIC are required']);
            return;
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

    private function uploadFile() {
        if (!isset($_FILES['file'])) {
            ResponseHelper::sendResponse(400, ['error' => 'File not uploaded']);
            return;
        }
        $file = $_FILES['file'];
    
        // Validate file type
        $allowedTypes = ['application/pdf', 'text/plain'];
        if (!in_array($file['type'], $allowedTypes)) {
            ResponseHelper::sendResponse(400, ['error' => 'Only PDF and TXT files are allowed']);
            return;
        }
    
        // limit file size -max 5MB
        $maxFileSize = 5 * 1024 * 1024; 
        if ($file['size'] > $maxFileSize) {
            ResponseHelper::sendResponse(400, ['error' => 'File size exceeds the limit of 5MB']);
            return;
        }
    
        // Sanitize file name
        $fileName = basename($file['name']);
        $fileName = preg_replace('/[^a-zA-Z0-9-_\.]/', '', $fileName);
    
        // "Uploads" directory 
        $uploadDir = './Uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
    
        $uploadPath = $uploadDir . $fileName;
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
           
    
            ResponseHelper::sendResponse(200, ['message' => 'File uploaded successfully']);
        } else {
            ResponseHelper::sendResponse(500, ['error' => 'Failed to upload file']);
        }
    }
    
}
?>
