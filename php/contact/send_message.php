<?php
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendResponse(false, 'Method not allowed');
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$name = trim((string) ($input['name'] ?? ''));
$email = trim((string) ($input['email'] ?? ''));
$subject = trim((string) ($input['subject'] ?? ''));
$message = trim((string) ($input['message'] ?? ''));

if ($name === '' || $email === '' || $subject === '' || $message === '') {
    sendResponse(false, 'Missing required fields');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(false, 'A valid email address is required');
}

$record = [
    'id' => strtoupper(bin2hex(random_bytes(4))),
    'name' => $name,
    'email' => $email,
    'subject' => $subject,
    'message' => $message,
    'created_at' => date('c')
];

storeContactMessage($record);

sendResponse(true, 'Your message has been sent successfully. We will respond shortly.', ['message' => $record]);
?>
