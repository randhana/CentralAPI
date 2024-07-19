<?php

class ResponseHelper {
    private static $logger;

    public static function setLogger($logger) {
        self::$logger = $logger;
    }

    public static function sendResponse($status, $data) {
        if (self::$logger === null) {
            throw new Exception('Logger not set');
        }
        
        http_response_code($status);
        header('Content-Type: application/json');
        $response = json_encode($data);
        echo $response;

        // Log the response
        self::$logger->info('Outgoing response', [
            'status' => $status,
            'response' => $response
        ]);
    }
}

?>
