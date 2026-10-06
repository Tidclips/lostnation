<?php
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendResponse(false, 'Method not allowed');
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$firstName = trim((string) ($input['firstName'] ?? ''));
$lastName = trim((string) ($input['lastName'] ?? ''));
$email = strtolower(trim((string) ($input['email'] ?? '')));
$phoneNumber = trim((string) ($input['phoneNumber'] ?? ''));
$password = (string) ($input['password'] ?? '');

if ($firstName === '' || $lastName === '' || $email === '' || $phoneNumber === '' || $password === '') {
    sendResponse(false, 'Missing required fields');
}

$users = readJsonStore(APP_USERS_FILE, []);
foreach ($users as $user) {
    if (strtolower((string) ($user['email'] ?? '')) === $email) {
        sendResponse(false, 'An account with this email already exists');
    }
}

$id = 1;
if (!empty($users)) {
    $id = max(array_map(fn($user) => (int) ($user['id'] ?? 1), $users)) + 1;
}

$user = [
    'id' => $id,
    'firstName' => $firstName,
    'lastName' => $lastName,
    'email' => $email,
    'phoneNumber' => $phoneNumber,
    'address' => '',
    'password' => password_hash($password, PASSWORD_DEFAULT),
    'created_at' => date('c')
];

$users[] = $user;
writeJsonStore(APP_USERS_FILE, $users);

$token = generateToken();
$_SESSION['user_id'] = $id;
$_SESSION['user_email'] = $email;
$_SESSION['user_token'] = $token;

sendResponse(true, 'Registration successful', [
    'customer' => [
        'id' => $id,
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $email,
        'phoneNumber' => $phoneNumber,
        'address' => '',
        'token' => $token
    ]
]);
?>
