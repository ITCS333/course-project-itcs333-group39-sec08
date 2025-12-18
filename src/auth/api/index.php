<?php
session_start();

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST requests are allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    $data = $_POST;
}

if (!isset($data['email']) || !isset($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Email and password required']);
    exit;
}

$email = trim($data['email']);
$password = $data['password'];

// Debug logging
error_log("Login attempt - Email: " . $email . ", Password length: " . strlen($password));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    error_log("Invalid email format: " . $email);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

$dbPath = __DIR__ . '/../../../../includes/db.php';
if (!file_exists($dbPath)) {
    echo json_encode(['success' => false, 'message' => 'Database configuration not found']);
    exit;
}

require_once $dbPath;

try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

try {
    $sql = "SELECT id, name, email, password, is_admin FROM users WHERE email = :email LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        error_log("User not found: " . $email);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email or password'
        ]);
        exit;
    }

    error_log("User found: " . $email . ", Checking password...");
    $passVerify = password_verify($password, $user['password']);
    error_log("Password verify result: " . ($passVerify ? 'TRUE' : 'FALSE'));

    if (!$passVerify) {
        error_log("Password mismatch for user: " . $email);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email or password'
        ]);
        exit;
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['logged_in'] = true;
    $_SESSION['user_email'] = $user['email'];

    if ($user['is_admin'] == 1) {
        $_SESSION['role'] = 'admin';
        $_SESSION['user_type'] = 'admin';
        $_SESSION['user_name'] = $user['name'];
        $redirectUrl = '../admin/manage_users.html';
    } else {
        $_SESSION['role'] = 'student';
        $_SESSION['user_type'] = 'student';
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['student_id'] = $user['id'];
        $redirectUrl = '../../index.html';
    }

    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'redirect' => $redirectUrl
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred'
    ]);
    exit;
}
?>
