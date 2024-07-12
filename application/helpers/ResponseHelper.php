<?php

class ResponseHelper {
    public static function sendResponse($status, $data) {
        global $log; 
        http_response_code($status);
        header('Content-Type: application/json');
        $response = json_encode($data);
        echo $response;
        
        // Log the response
        $log->info('Outgoing response', [
            'status' => $status,
            'response' => $response
        ]);
    }
}

?>
