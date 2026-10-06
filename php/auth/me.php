<?php
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    sendResponse(false, 'Method not allowed');
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    sendResponse(false, 'No token provided');
}

$userId = (int) $_SESSION['user_id'];
$users = readJsonStore(APP_USERS_FILE, []);

foreach ($users as $user) {
    if ((int) ($user['id'] ?? 0) === $userId) {
        sendResponse(true, 'User profile loaded', ['customer' => [
            'id' => (int) $user['id'],
            'firstName' => (string) ($user['firstName'] ?? ''),
            'lastName' => (string) ($user['lastName'] ?? ''),
            'email' => (string) ($user['email'] ?? ''),
            'phoneNumber' => (string) ($user['phoneNumber'] ?? ''),
            'address' => (string) ($user['address'] ?? ''),
            'token' => $_SESSION['user_token'] ?? ''
        ]]);
    }
}

http_response_code(404);
sendResponse(false, 'User not found');
