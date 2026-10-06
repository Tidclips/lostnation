<?php
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    sendResponse(false, 'No token provided');
}

$customerId = (int) $_SESSION['user_id'];
sendResponse(true, 'Token verified', ['customerId' => $customerId]);
?>
