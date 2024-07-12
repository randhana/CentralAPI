<?php

require_once('../config/config.php');
require '../vendor/autoload.php';
require_once('helpers/ResponseHelper.php');
require_once('helpers/TokenHelper.php');
require_once('helpers/RateLimiter.php');
require_once('controllers/AuthController.php');
require_once('controllers/EmployeeController.php');

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

date_default_timezone_set('Asia/Colombo');

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Log channel
$log = new Logger('api_logger');
$log->pushHandler(new StreamHandler('../logs/api.log', Logger::DEBUG));

$log->info('Incoming request', [
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'],
    'ip' => $_SERVER['REMOTE_ADDR'],
    'body' => file_get_contents('php://input')
]);

$request_uri = strtok($_SERVER["REQUEST_URI"], '?');
$parts = explode('.php/', $request_uri);

if (count($parts) < 2) {
    $log->warning('Invalid URL format');
    ResponseHelper::sendResponse(400, ['error' => 'Invalid URL format']);
    exit;
}

$endpoint_and_params = explode('/', $parts[1]);
$endpoint = $endpoint_and_params[0];
$id = $endpoint_and_params[1] ?? '';

$ipAddress = $_SERVER['REMOTE_ADDR'];

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            if ($endpoint === 'getAccessToken') {
                RateLimiter::rateLimit($ipAddress, 8);
                $authController = new AuthController($apiDb);
                $authController->getAccessToken();
                if (!$authController) {
                    exit;
                }
            } else {
                $token = TokenHelper::getBearerToken();
                if (!$token) {
                    $log->warning('Authorization token not provided');
                    RateLimiter::rateLimit($ipAddress, 8);
                    ResponseHelper::sendResponse(401, ['error' => 'Authorization token not provided']);
                    exit;
                }

                $user = TokenHelper::verifyToken($apiDb, $token);
                if (!$user) {
                    exit;
                }

                RateLimiter::rateLimit($ipAddress, 20);
                $employeeController = new EmployeeController($masterDb);
                $employeeController->handlePOSTRequest($endpoint);
            }
            break;

        case 'GET':
            $token = TokenHelper::getBearerToken();
            if (!$token) {
                $log->warning('Authorization token not provided');
                RateLimiter::rateLimit($ipAddress, 8);
                ResponseHelper::sendResponse(401, ['error' => 'Authorization token not provided']);
                exit;
            }

            $user = TokenHelper::verifyToken($apiDb, $token);
            if (!$user) {
                exit;
            }

            RateLimiter::rateLimit($ipAddress, 20);
            $employeeController = new EmployeeController($masterDb);
            $employeeController->handleGETRequest($endpoint);
            break;

        default:
            $log->warning('Method Not Allowed');
            ResponseHelper::sendResponse(405, ['error' => 'Method Not Allowed']);
    }
} catch (Exception $e) {
    $log->error('An error occurred', ['error' => $e->getMessage()]);
    ResponseHelper::sendResponse(500, ['error' => 'Internal Server Error']);
}
?>
