<?php
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    sendResponse(true, 'Payment endpoint ready', ['status' => 'ready']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = [];
    $rawBody = file_get_contents('php://input');
    if (!empty($rawBody)) {
        $decoded = json_decode($rawBody, true);
        if (is_array($decoded)) {
            $input = $decoded;
        }
    }

    if (empty($input) && !empty($_POST)) {
        $input = $_POST;
    }

    $amountInput = $input['amount'] ?? 87000;
    $email = trim((string) ($input['email'] ?? $_POST['email'] ?? ''));
    $item = trim((string) ($input['item'] ?? 'Lostnation order'));

    if (!is_numeric($amountInput) || (float) $amountInput <= 0) {
        http_response_code(400);
        sendResponse(false, 'A valid positive amount is required');
    }

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        sendResponse(false, 'A valid email address is required');
    }

    $user = getCurrentUser();
    $amount = (float) $amountInput;
    $paymentReference = 'LN-' . strtoupper(bin2hex(random_bytes(5)));

    $order = [
        'id' => $paymentReference,
        'customer_id' => $user ? (int) $user['id'] : null,
        'customer_email' => $email,
        'item' => $item,
        'amount' => number_format($amount, 2, '.', ''),
        'status' => 'paid',
        'created_at' => date('c')
    ];

    $payment = [
        'id' => $paymentReference,
        'customer_id' => $user ? (int) $user['id'] : null,
        'customer_email' => $email,
        'amount' => number_format($amount, 2, '.', ''),
        'status' => 'paid',
        'created_at' => date('c')
    ];

    storeOrder($order);
    storePayment($payment);

    if (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'text/html') !== false) {
        header('Location: ../../public/index.html?payment=success');
        exit();
    }

    sendResponse(true, 'Order placed successfully.', [
        'paymentReference' => $paymentReference,
        'amount' => $amount,
        'customerEmail' => $email,
        'status' => 'paid',
        'message' => 'Payment confirmed and order saved.'
    ]);
}

http_response_code(405);
sendResponse(false, 'Method not allowed');
?>
