<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

function initializeLoggers() {
    //request logs
    $requestLogger = new Logger('request_logger');
    $requestLogger->setTimezone(new \DateTimeZone('Asia/Colombo'));
    $requestLogger->pushHandler(new StreamHandler('../logs/requests.log', Logger::INFO));

    //error logs
    $errorLogger = new Logger('error_logger');
    $requestLogger->setTimezone(new \DateTimeZone('Asia/Colombo'));
    $errorLogger->pushHandler(new StreamHandler('../logs/errors.log', Logger::ERROR));

    return [
        'requestLogger' => $requestLogger,
        'errorLogger' => $errorLogger
    ];
}

?>
