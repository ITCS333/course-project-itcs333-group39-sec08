<?php
/**
 * Authentication Handler for Login Form
 */

// --- Session Management ---
session_start();

// --- Set Response Headers ---
header('Content-Type: application/json');

// --- Check Request Method ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST requests are allowed']);
    exit;
}

// --- Get POST Data ---
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

// Check if data was received
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

// Check required fields
if (!isset($data['email']) || !isset($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Email and password required']);
    exit;
}

// Get and validate email
$email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
$password = $data['password'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
    exit;
}

// --- Database Connection ---
try {
    // Database configuration (update with your actual credentials)
    $host = 'localhost';
    $dbname = 'your_database';
    $username = 'your_username';
    $dbpassword = 'your_password';
    
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $dbpassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    // --- Prepare SQL Query with prepared statement ---
    $sql = "SELECT id, name, email, password FROM users WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    
    // --- Execute the prepared statement ---
    $stmt->execute([':email' => $email]);
    
    // --- Fetch user data ---
    $user = $stmt->fetch();
    
    // --- Verify password ---
    if ($user && password_verify($password, $user['password'])) {
        // --- Store user data in session ---
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['logged_in'] = true;
        
        // Success response
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email']
            ]
        ]);
        exit;
    } else {
        // Invalid credentials
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email or password'
        ]);
        exit;
    }
    
} catch (PDOException $e) {
    // Handle database errors
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
    exit;
}

    // ---------------- DB ----------------
require_once __DIR__ . '/../../../../includes/db.php';
try {
   $database = new Database();
   $db = $database->getConnection();
} catch (Exception $e) {
   http_response_code(500);
   echo json_encode(['error' => 'Database connection failed']);
   exit;
}
?>
