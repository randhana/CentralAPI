<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

function initializeLoggers() {
    //request logs
    $requestLogger = new Logger('request_logger');
    $requestLogger->pushHandler(new StreamHandler('../logs/requests.log', Logger::INFO));

    //error logs
    $errorLogger = new Logger('error_logger');
    $errorLogger->pushHandler(new StreamHandler('../logs/errors.log', Logger::ERROR));

    return [
        'requestLogger' => $requestLogger,
        'errorLogger' => $errorLogger
    ];
}

?>
