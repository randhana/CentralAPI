<?php

require_once('./helpers/ResponseHelper.php');
require_once('./helpers/TokenHelper.php');
require_once('./helpers/RateLimiter.php');
require_once('./models/User.php');
use \Firebase\JWT\JWT;

class AuthController {
    private $apiDb;
    private $userModel;

    public function __construct($apiDb) {
        $this->apiDb = $apiDb;
        $this->userModel = new User($apiDb);
    }

    public function getAccessToken() {
        $privateKeyFile = './private_key.pem';
        $passphrase = 'master';

        $privateKey = openssl_pkey_get_private('file://' . $privateKeyFile, $passphrase);
        if (!$privateKey) {
            ResponseHelper::sendResponse(500, ['error' => 'Failed to load private key']);
        }

        $postData = json_decode(file_get_contents('php://input'), true);

        if (!isset($postData['username']) || !isset($postData['password'])) {
            ResponseHelper::sendResponse(400, ['error' => 'Username and password are required']);
            return false;
        }

        $username = $postData['username'];
        $password = $postData['password'];

        $user = $this->userModel->getUserByUsername($username);
        if (!$user || !password_verify($password, $user['password'])) {
            ResponseHelper::sendResponse(401, ['error' => 'Invalid username or password']);
            return false;
        }

        $issuedAt = time();
        $expirationTime = $issuedAt + 3600;
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'iss' => 'https://asiaassetfinance.com/',
            'data' => [
                'userId' => $user['id'],
                'username' => $username,
            ]
        ];

        $jwt = JWT::encode($payload, $privateKey, 'RS256');

        $updateToken = $jwt;
        $updateExpiry = date('Y-m-d H:i:s', $expirationTime);
        $updateUserId = $user['id'];

        $this->userModel->updateToken($updateUserId, $updateToken, $updateExpiry);

        ResponseHelper::sendResponse(200, ["access_token" => $jwt, "expires_in" => 3600]);
    }
}
