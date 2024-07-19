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
$requestLogger->info('Incoming request', [
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'],
    'ip' => $_SERVER['REMOTE_ADDR'],
    'body' => file_get_contents('php://input')
]);

// Process the request URI
$request_uri = strtok($_SERVER["REQUEST_URI"], '?');
$parts = explode('.php/', $request_uri);

if (count($parts) < 2) {
    $requestLogger->warning('Invalid URL format');
    ResponseHelper::sendResponse(400, ['error' => 'Invalid URL format']);
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
                $authController->getAccessToken();
            } else {
                $token = TokenHelper::getBearerToken();
                if (!$token) {
                    $requestLogger->warning('Authorization token not provided');
                    ResponseHelper::sendResponse(401, ['error' => 'Authorization token not provided']);
                    exit;
                }

                $user = TokenHelper::verifyToken($token);
                if (!$user) {
                    $requestLogger->warning('Invalid authorization token');
                    ResponseHelper::sendResponse(401, ['error' => 'Invalid authorization token']);
                    exit;
                }

                $employeeController = new EmployeeController($masterDb);
                $employeeController->handlePOSTRequest($endpoint);
            }
            break;

        case 'GET':
            $token = TokenHelper::getBearerToken();
            if (!$token) {
                $requestLogger->warning('Authorization token not provided');
                ResponseHelper::sendResponse(401, ['error' => 'Authorization token not provided']);
                exit;
            }

            $user = TokenHelper::verifyToken($token);
            if (!$user) {
                $requestLogger->warning('Invalid authorization token');
                ResponseHelper::sendResponse(401, ['error' => 'Invalid authorization token']);
                exit;
            }

            $employeeController = new EmployeeController($masterDb);
            $employeeController->handleGETRequest($endpoint);
            break;

        default:
            $requestLogger->warning('Method Not Allowed');
            ResponseHelper::sendResponse(405, ['error' => 'Method Not Allowed']);
    }
} catch (Exception $e) {
    $errorLogger->error('An error occurred', ['error' => $e->getMessage()]);
    ResponseHelper::sendResponse(500, ['error' => 'Internal Server Error']);
}
?>
