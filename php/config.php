<?php
session_start();
date_default_timezone_set('Africa/Lagos');

define('APP_DATA_DIR', rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'lostnation-data');
define('APP_USERS_FILE', APP_DATA_DIR . DIRECTORY_SEPARATOR . 'users.json');
define('APP_ORDERS_FILE', APP_DATA_DIR . DIRECTORY_SEPARATOR . 'orders.json');
define('APP_PAYMENTS_FILE', APP_DATA_DIR . DIRECTORY_SEPARATOR . 'payments.json');
define('APP_CONTACT_MESSAGES_FILE', APP_DATA_DIR . DIRECTORY_SEPARATOR . 'contact_messages.json');

if (!is_dir(APP_DATA_DIR)) {
    mkdir(APP_DATA_DIR, 0777, true);
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function ensureJsonFile(string $path, array $default = []): array {
    if (!file_exists($path)) {
        file_put_contents($path, json_encode($default, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return $default;
    }

    $raw = file_get_contents($path);
    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : $default;
}

function readJsonStore(string $path, array $fallback = []): array {
    return ensureJsonFile($path, $fallback);
}

function writeJsonStore(string $path, array $data): bool {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    return (bool) file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function sendResponse($success, $message, $data = null) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }

    $response = ['success' => $success, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

function generateToken(): string {
    return bin2hex(random_bytes(16));
}

function getCurrentUserId(): ?int {
    $userId = $_SESSION['user_id'] ?? null;
    if ($userId === null || $userId === '') {
        return null;
    }

    return (int) $userId;
}

function getCurrentUser(): ?array {
    $userId = getCurrentUserId();
    if ($userId === null) {
        return null;
    }

    foreach (readJsonStore(APP_USERS_FILE, []) as $user) {
        if ((int) ($user['id'] ?? 0) === $userId) {
            return $user;
        }
    }

    return null;
}

function requireAuth(): ?array {
    $user = getCurrentUser();
    if ($user === null) {
        sendResponse(false, 'Unauthorized');
    }

    return $user;
}

function storeUser(array $user): bool {
    $users = readJsonStore(APP_USERS_FILE, []);
    $users[] = $user;
    return writeJsonStore(APP_USERS_FILE, $users);
}

function storeOrder(array $order): bool {
    $orders = readJsonStore(APP_ORDERS_FILE, []);
    $orders[] = $order;
    return writeJsonStore(APP_ORDERS_FILE, $orders);
}

function storePayment(array $payment): bool {
    $payments = readJsonStore(APP_PAYMENTS_FILE, []);
    $payments[] = $payment;
    return writeJsonStore(APP_PAYMENTS_FILE, $payments);
}

function storeContactMessage(array $message): bool {
    $messages = readJsonStore(APP_CONTACT_MESSAGES_FILE, []);
    $messages[] = $message;
    return writeJsonStore(APP_CONTACT_MESSAGES_FILE, $messages);
}
?>
