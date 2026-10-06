<?php
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendResponse(false, 'Method not allowed');
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$email = strtolower(trim((string) ($input['email'] ?? '')));
$password = (string) ($input['password'] ?? '');

if ($email === '' || $password === '') {
    sendResponse(false, 'Missing required fields');
}

$users = readJsonStore(APP_USERS_FILE, []);
$user = null;

foreach ($users as $record) {
    if (strtolower((string) ($record['email'] ?? '')) === $email) {
        $user = $record;
        break;
    }
}

if ($user === null || !isset($user['password']) || !password_verify($password, $user['password'])) {
    sendResponse(false, 'Invalid email or password');
}

$token = generateToken();
$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_token'] = $token;

sendResponse(true, 'Login successful', [
    'customer' => [
        'id' => (int) $user['id'],
        'firstName' => (string) ($user['firstName'] ?? ''),
        'lastName' => (string) ($user['lastName'] ?? ''),
        'email' => (string) $user['email'],
        'phoneNumber' => (string) ($user['phoneNumber'] ?? ''),
        'address' => (string) ($user['address'] ?? ''),
        'token' => $token
    ]
]);
?>
