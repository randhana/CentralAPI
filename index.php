<?php

$request_uri = $_SERVER['REQUEST_URI'];
$parts = explode('.php', $request_uri);

if (count($parts) < 2) {
    http_response_code(400);
    echo json_encode(array('error' => 'Invalid URL format'));
    exit;
}

$endpoint_and_params = explode('/', $parts[1]);
$endpoint = $endpoint_and_params[0];
$endpoint_file = '/application/endpoints/' . $endpoint . '.php';

if (file_exists($endpoint_file)) {
    require_once($endpoint_file);
} else {
    http_response_code(400);
    echo json_encode(array('error' => 'Invalid endpoint'));
    exit;
}
?>
