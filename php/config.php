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

function getDatabaseConnection(): ?PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    if (!class_exists('PDO')) {
        return null;
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $dbname = getenv('DB_NAME') ?: 'lostnation';
    $user = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';

    try {
        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return $pdo;
    } catch (Throwable $e) {
        error_log('Lostnation DB connection unavailable: ' . $e->getMessage());
        return null;
    }
}

function ensureDatabaseSchema(): void {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        return;
    }

    $statements = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            firstName VARCHAR(100) NOT NULL,
            lastName VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            phoneNumber VARCHAR(50) DEFAULT '',
            address TEXT DEFAULT '',
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS payments (
            id VARCHAR(100) PRIMARY KEY,
            customer_id INT NULL,
            customer_email VARCHAR(255) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'paid',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS contact_messages (
            id VARCHAR(100) PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS orders (
            id VARCHAR(100) PRIMARY KEY,
            customer_id INT NULL,
            customer_email VARCHAR(255) NOT NULL,
            item VARCHAR(255) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'paid',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            color VARCHAR(100),
            image_url TEXT,
            stock INT DEFAULT 0,
            is_featured TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];

    foreach ($statements as $statement) {
        try {
            $pdo->exec($statement);
        } catch (Throwable $e) {
            error_log('Lostnation schema setup warning: ' . $e->getMessage());
        }
    }
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
    $pdo = getDatabaseConnection();
    if ($pdo) {
        ensureDatabaseSchema();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (firstName, lastName, email, phoneNumber, address, password, created_at)
                 VALUES (:firstName, :lastName, :email, :phoneNumber, :address, :password, NOW())
                 ON DUPLICATE KEY UPDATE firstName = VALUES(firstName), lastName = VALUES(lastName), phoneNumber = VALUES(phoneNumber), address = VALUES(address), password = VALUES(password)'
            );
            $stmt->execute([
                ':firstName' => $user['firstName'] ?? '',
                ':lastName' => $user['lastName'] ?? '',
                ':email' => $user['email'] ?? '',
                ':phoneNumber' => $user['phoneNumber'] ?? '',
                ':address' => $user['address'] ?? '',
                ':password' => $user['password'] ?? ''
            ]);
            return true;
        } catch (Throwable $e) {
            error_log('Lostnation user DB insert failed: ' . $e->getMessage());
        }
    }

    $users = readJsonStore(APP_USERS_FILE, []);
    $users[] = $user;
    return writeJsonStore(APP_USERS_FILE, $users);
}

function storeOrder(array $order): bool {
    $pdo = getDatabaseConnection();
    if ($pdo) {
        ensureDatabaseSchema();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO orders (id, customer_id, customer_email, item, amount, status, created_at)
                 VALUES (:id, :customer_id, :customer_email, :item, :amount, :status, NOW())
                 ON DUPLICATE KEY UPDATE customer_id = VALUES(customer_id), customer_email = VALUES(customer_email), item = VALUES(item), amount = VALUES(amount), status = VALUES(status)'
            );
            $stmt->execute([
                ':id' => $order['id'] ?? '',
                ':customer_id' => $order['customer_id'] ?? null,
                ':customer_email' => $order['customer_email'] ?? '',
                ':item' => $order['item'] ?? '',
                ':amount' => $order['amount'] ?? 0,
                ':status' => $order['status'] ?? 'paid'
            ]);
            return true;
        } catch (Throwable $e) {
            error_log('Lostnation order DB insert failed: ' . $e->getMessage());
        }
    }

    $orders = readJsonStore(APP_ORDERS_FILE, []);
    $orders[] = $order;
    return writeJsonStore(APP_ORDERS_FILE, $orders);
}

function storePayment(array $payment): bool {
    $pdo = getDatabaseConnection();
    if ($pdo) {
        ensureDatabaseSchema();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO payments (id, customer_id, customer_email, amount, status, created_at)
                 VALUES (:id, :customer_id, :customer_email, :amount, :status, NOW())
                 ON DUPLICATE KEY UPDATE customer_id = VALUES(customer_id), customer_email = VALUES(customer_email), amount = VALUES(amount), status = VALUES(status)'
            );
            $stmt->execute([
                ':id' => $payment['id'] ?? '',
                ':customer_id' => $payment['customer_id'] ?? null,
                ':customer_email' => $payment['customer_email'] ?? '',
                ':amount' => $payment['amount'] ?? 0,
                ':status' => $payment['status'] ?? 'paid'
            ]);
            return true;
        } catch (Throwable $e) {
            error_log('Lostnation payment DB insert failed: ' . $e->getMessage());
        }
    }

    $payments = readJsonStore(APP_PAYMENTS_FILE, []);
    $payments[] = $payment;
    return writeJsonStore(APP_PAYMENTS_FILE, $payments);
}

function storeContactMessage(array $message): bool {
    $pdo = getDatabaseConnection();
    if ($pdo) {
        ensureDatabaseSchema();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO contact_messages (id, name, email, subject, message, created_at)
                 VALUES (:id, :name, :email, :subject, :message, NOW())'
            );
            $stmt->execute([
                ':id' => $message['id'] ?? '',
                ':name' => $message['name'] ?? '',
                ':email' => $message['email'] ?? '',
                ':subject' => $message['subject'] ?? '',
                ':message' => $message['message'] ?? ''
            ]);
            return true;
        } catch (Throwable $e) {
            error_log('Lostnation contact DB insert failed: ' . $e->getMessage());
        }
    }

    $messages = readJsonStore(APP_CONTACT_MESSAGES_FILE, []);
    $messages[] = $message;
    return writeJsonStore(APP_CONTACT_MESSAGES_FILE, $messages);
}
?>
