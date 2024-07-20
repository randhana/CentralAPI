<?php

require_once('../config/config.php');
require '../vendor/autoload.php';
require_once('helpers/ResponseHelper.php');
require_once('helpers/TokenHelper.php');
require_once('helpers/RateLimiter.php');
require_once('controllers/AuthController.php');
require_once('controllers/EmployeeController.php');
require_once('logger.php');

//init logger
$loggers = initializeLoggers();
$requestLogger = $loggers['requestLogger'];
$errorLogger = $loggers['errorLogger'];

//init ResponseHelper
ResponseHelper::setLogger($requestLogger);

//init TokenHelper
TokenHelper::initializeLogger($requestLogger);

//init Redis
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

//init RateLimiter
$rateLimiter = new RateLimiter($redis, 20, 60);

// Set timezone
date_default_timezone_set('Asia/Colombo');

// Log the incoming request
$requestId = uniqid();

$requestLogger->info('Incoming request', [
    'request_id' => $requestId,
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'],
    'ip' => $_SERVER['REMOTE_ADDR'],
    'body' => file_get_contents('php://input')
]);

// Process the request URI
$request_uri = strtok($_SERVER["REQUEST_URI"], '?');
$parts = explode('.php/', $request_uri);

if (count($parts) < 2) {
    ResponseHelper::sendResponse(400, ['error' => 'Invalid URL format'], $requestId);
    exit;
}

$endpoint_and_params = explode('/', $parts[1]);
$endpoint = $endpoint_and_params[0];
$id = $endpoint_and_params[1] ?? '';

$ipAddress = $_SERVER['REMOTE_ADDR'];

try {
    // Rate limiting check
    if (!$rateLimiter->rateLimit($ipAddress)) {
        exit;
    }

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            if ($endpoint === 'getAccessToken') {
                $authController = new AuthController($apiDb);
                $authController->getAccessToken($requestId);
            } else {
                $token = TokenHelper::getBearerToken();
                if (!$token) {
                    ResponseHelper::sendResponse(401, ['error' => 'Authorization token not provided'], $requestId);
                    exit;
                }

                $tokenResult = TokenHelper::verifyToken($token);
                if ($tokenResult === 'expired') {
                    ResponseHelper::sendResponse(401, ['error' => 'Token has expired'], $requestId);
                    exit;
                }
                
                if ($tokenResult === 'invalid' || !$tokenResult) {
                    ResponseHelper::sendResponse(401, ['error' => 'Invalid authorization token'], $requestId);
                    exit;
                }

                $employeeController = new EmployeeController($masterDb);
                $employeeController->handlePOSTRequest($endpoint, $requestId);
            }
            break;

        case 'GET':
            $token = TokenHelper::getBearerToken();
            if (!$token) {
                ResponseHelper::sendResponse(401, ['error' => 'Authorization token not provided'], $requestId);
                exit;
            }

            $tokenResult = TokenHelper::verifyToken($token);
                if ($tokenResult === 'expired') {
                    ResponseHelper::sendResponse(401, ['error' => 'Token has expired'], $requestId);
                    exit;
                }
                
                if ($tokenResult === 'invalid' || !$tokenResult) {
                    ResponseHelper::sendResponse(401, ['error' => 'Invalid authorization token'], $requestId);
                    exit;
                }

            $employeeController = new EmployeeController($masterDb);
            $employeeController->handleGETRequest($endpoint, $requestId);
            break;

        default:
            ResponseHelper::sendResponse(405, ['error' => 'Method Not Allowed'], $requestId);
    }
} catch (Exception $e) {
    $errorLogger->error('An error occurred', ['error' => $e->getMessage()]);
    ResponseHelper::sendResponse(500, ['error' => 'Internal Server Error'], $requestId);
}
?>
