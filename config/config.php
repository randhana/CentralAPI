<?php
define('DEVELOPMENT_ENVIRONMENT', false);

define('API_DB_NAME', 'api');
define('MASTER_DB_NAME', 'api_master');
define('DB_USER', 'root');
define('DB_PASSWORD', '0000');
define('DB_HOST', 'localhost');

// api
try {
    $apiDb = new PDO('mysql:host=' . DB_HOST . ';dbname=' . API_DB_NAME, DB_USER, DB_PASSWORD);
    $apiDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('API Database connection failed: ' . $e->getMessage());
}

// masterDb
try {
    $masterDb = new PDO('mysql:host=' . DB_HOST . ';dbname=' . MASTER_DB_NAME, DB_USER, DB_PASSWORD);
    $masterDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('MASTER Database connection failed: ' . $e->getMessage());
}
