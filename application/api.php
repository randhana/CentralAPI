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

$request_uri = strtok($_SERVER["REQUEST_URI"], '?');
$parts = explode('.php/', $request_uri);

if (count($parts) < 2) {
    ResponseHelper::sendResponse(400, ['error' => 'Invalid URL format']);
}

$endpoint_and_params = explode('/', $parts[1]);
$endpoint = $endpoint_and_params[0];
$id = $endpoint_and_params[1] ?? '';

$ipAddress = $_SERVER['REMOTE_ADDR'];

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        if ($endpoint === 'getAccessToken') {
            RateLimiter::rateLimit($ipAddress, 8); // Limit to 8 requests per minute
            $authController = new AuthController($apiDb);
            $authController->getAccessToken();
        } elseif ($endpoint === 'createEmployee') {
            $token = TokenHelper::getBearerToken();
            if (!$token) {
                ResponseHelper::sendResponse(401, ['error' => 'Authorization token not provided']);
            }

            $user = TokenHelper::verifyToken($apiDb, $token);
            if (!$user) {
                ResponseHelper::sendResponse(401, ['error' => 'Invalid token']);
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                ResponseHelper::sendResponse(405, ['error' => 'Method Not Allowed']);
            }

            RateLimiter::rateLimit($ipAddress, 8); // Limit to 8 requests per minute
            $employeeController = new EmployeeController($masterDb);
            $employeeController->createEmployee();
        } else {
            ResponseHelper::sendResponse(405, ['error' => 'Method Not Allowed']);
        }
        break;

    case 'GET':
        $token = TokenHelper::getBearerToken();
        if (!$token) {
            ResponseHelper::sendResponse(401, ['error' => 'Authorization token not provided']);
        }

        $user = TokenHelper::verifyToken($apiDb, $token);
        if (!$user) {
            ResponseHelper::sendResponse(401, ['error' => 'Invalid token']);
        }

        RateLimiter::rateLimit($ipAddress, 8); // Limit to 8 requests per minute

        $employeeController = new EmployeeController($masterDb);
        $employeeController->handleRequest($endpoint);
        break;

    default:
        ResponseHelper::sendResponse(405, ['error' => 'Method Not Allowed']);
}
?>
