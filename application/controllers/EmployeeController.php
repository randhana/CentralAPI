<?php

require_once('./helpers/ResponseHelper.php');
require_once('./helpers/TokenHelper.php');
require_once('./helpers/RateLimiter.php');
require_once('./models/Employee.php');
require_once('logger.php');

class EmployeeController {
    private $employeeModel;
    private $redis;
    private $requestLogger;
    private $errorLogger;

    public function __construct($masterDb, $redis) {
        $this->employeeModel = new Employee($masterDb);
        $this->redis = $redis;

        // Initialize logger
        $loggers = initializeLoggers();
        $this->requestLogger = $loggers['requestLogger'];
        $this->errorLogger = $loggers['errorLogger'];
    }

    public function handleGETRequest($endpoint, $requestId) {
        $postData = json_decode(file_get_contents('php://input'), true);

        // Check if JSON is invalid
        if (json_last_error() !== JSON_ERROR_NONE) {
            ResponseHelper::sendResponse(400, ['error' => 'Invalid JSON format'], $requestId);
            return;
        }

        $id = $postData['id'] ?? '';
        try {
            switch ($endpoint) {
                case 'getList':
                    $result = $this->employeeModel->getList();
                    break;
                case 'getStatus':
                    if (empty($id)) {
                        ResponseHelper::sendResponse(400, ['error' => 'NIC is required for getStatus'], $requestId);
                        return;
                    }
                    
                    // Check Redis cache
                    $cacheKey = "employee_status_" . $id;
                    $cachedResult = $this->redis->get($cacheKey);

                    if ($cachedResult) {
                        $result = json_decode($cachedResult, true);
                        
                    } else {
                        // If not cached, fetch from the database
                        $result = $this->employeeModel->getStatus($id);
                        if ($result) {
                            // Cache the result for 30 minutes
                            $this->redis->setex($cacheKey, 1800, json_encode($result));
                        }
                    }
                    break;
                case 'getFullName':
                    if (empty($id)) {
                        ResponseHelper::sendResponse(400, ['error' => 'NIC is required for getFullName'], $requestId);
                        return;
                    }
                    
                    // Check Redis cache
                    $cacheKey = "employee_fullname_" . $id;
                    $cachedResult = $this->redis->get($cacheKey);

                    if ($cachedResult) {
                        $result = json_decode($cachedResult, true);
                    } else {
                        // If not cached, fetch from the database
                        $result = $this->employeeModel->getFullName($id);
                        if ($result) {
                            // Cache the result for 30 minutes
                            $this->redis->setex($cacheKey, 1800, json_encode($result));
                        }
                    }
                    break;
                default:
                    ResponseHelper::sendResponse(400, ['error' => 'Invalid Endpoint'], $requestId);
                    return;
            }

            if (!$result) {
                ResponseHelper::sendResponse(404, ['error' => 'NIC not found'], $requestId);
                return;
            }

            ResponseHelper::sendResponse(200, $result, $requestId);
        } catch (Exception $e) {
            $this->errorLogger->error('Error handling GET request', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            ResponseHelper::sendResponse(500, ['error' => 'Internal Server Error'], $requestId);
        }
    }

    public function handlePOSTRequest($endpoint, $requestId) {
        $postData = json_decode(file_get_contents('php://input'), true);

        try {
            switch ($endpoint) {
                case 'createEmployee':
                    $this->createEmployee($postData, $requestId);
                    break;
                case 'uploadFile':
                    $this->uploadFile($requestId);
                    break;
                default:
                    ResponseHelper::sendResponse(400, ['error' => 'Invalid Endpoint'], $requestId);
                    return;
            }
        } catch (Exception $e) {
            $this->errorLogger->error('Error handling POST request', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            ResponseHelper::sendResponse(500, ['error' => 'Internal Server Error'], $requestId);
        }
    }

    private function createEmployee($postData, $requestId) {
        if (!isset($postData['fullName']) || !isset($postData['nic'])) {
            ResponseHelper::sendResponse(400, ['error' => 'Full name and NIC are required'], $requestId);
            return;
        }

        $fullName = $postData['fullName'];
        $nic = $postData['nic'];

        try {
            $result = $this->employeeModel->create($fullName, $nic);
            ResponseHelper::sendResponse(201, ['message' => 'Employee created successfully', 'employee_id' => $result], $requestId);
        } catch (PDOException $e) {
            $this->errorLogger->error('Database error in createEmployee', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            ResponseHelper::sendResponse(500, ['error' => 'Database error'], $requestId);
        }
    }

    private function uploadFile($requestId) {
        if (!isset($_FILES['file'])) {
            ResponseHelper::sendResponse(400, ['error' => 'File not uploaded'], $requestId);
            return;
        }

        $file = $_FILES['file'];

        // Validate file type
        $allowedTypes = ['application/pdf', 'text/plain'];
        if (!in_array($file['type'], $allowedTypes)) {
            ResponseHelper::sendResponse(400, ['error' => 'Only PDF and TXT files are allowed'], $requestId);
            return;
        }

        // Limit file size - max 5MB
        $maxFileSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxFileSize) {
            ResponseHelper::sendResponse(400, ['error' => 'File size exceeds the limit of 5MB'], $requestId);
            return;
        }

        // Sanitize file name
        $fileName = basename($file['name']);
        $fileName = preg_replace('/[^a-zA-Z0-9-_\.]/', '', $fileName);

        // "Uploads" directory 
        $uploadDir = '../Uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $uploadPath = $uploadDir . $fileName;
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $this->requestLogger->info('File uploaded successfully', ['file' => $fileName]);
            ResponseHelper::sendResponse(200, ['message' => 'File uploaded successfully'], $requestId);
        } else {
            $this->errorLogger->error('Failed to upload file', [
                'file' => $fileName,
                'error' => 'Failed to move uploaded file'
            ]);
            ResponseHelper::sendResponse(500, ['error' => 'Failed to upload file'], $requestId);
        }
    }
}
?>
