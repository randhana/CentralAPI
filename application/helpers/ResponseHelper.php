<?php
require_once('logger.php');

class ResponseHelper {
    private static $requestLogger; 

    public static function setLogger() {
        // Initialize loggers 
        $loggers = initializeLoggers();
        self::$requestLogger = $loggers['requestLogger']; 
    }

    public static function sendSuccess($data, $requestId = null) {
        self::sendResponse(200, $data, $requestId); 
    }

    public static function sendError($errorMessage, $errorCode = 400, $requestId = null) {
        self::sendResponse($errorCode, [
            'error' => $errorMessage,
        ], $requestId);
    }

    public static function sendResponse($status, $data, $requestId = null) {
        if (!self::$requestLogger) {
            throw new \Exception('Logger is not initialized.');
        }

        // Ensure valid JSON response
        if (!is_array($data)) {
            $data = ['error' => 'Invalid response format'];
            $status = 500;
        }

        // Send headers
        http_response_code($status);
        header('Content-Type: application/json');

        // Encode the response
        $response = json_encode($data);

        // Send Content-Length header
        header('Content-Length: ' . strlen($response));

        // Output the response
        echo $response;

        // Log the response
        self::$requestLogger->info('Outgoing Response', [
            'request_id' => $requestId,
            'status' => $status,
            'response' => $response,
        ]);
    }
}
?>
