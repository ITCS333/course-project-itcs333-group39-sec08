<?php
/**
 * Authentication Handler for Student/Admin Login
 */

// --- Session Management ---
session_start();

// --- Set Response Headers ---
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// --- Handle Preflight Requests ---
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --- Check Request Method ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST requests are allowed']);
    exit;
}

// --- Get POST Data ---
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

// Fallback to form data if JSON parsing fails
if (!$data) {
    $data = $_POST;
}

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
    // Include database connection
    require_once __DIR__ . '/../../../includes/db.php';
    
    // Create database connection
    $database = new Database();
    $pdo = $database->getConnection();
    
    // --- Check if user exists in students table ---
    $sql = "SELECT id, student_id, name, email, password FROM students WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    
    // --- Verify password ---
    if ($user && password_verify($password, $user['password'])) {
        // --- Store user data in session ---
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['student_id'] = $user['student_id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['logged_in'] = true;
        $_SESSION['user_type'] = 'student'; // Or 'admin' for admin users
        
        // Determine redirect URL based on user role
        $redirectUrl = '/student-dashboard.php';
        
        // Check if it's an admin (you might have an admin table or flag)
        // For now, let's assume admin has a specific email pattern
        if (strpos($email, 'admin') !== false || $email === 'admin@example.com') {
            $_SESSION['user_type'] = 'admin';
            $redirectUrl = '/admin.html';
        }
        
        // Success response
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'student_id' => $user['student_id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'type' => $_SESSION['user_type']
            ],
            'redirect' => $redirectUrl
        ]);
        exit;
    } else {
        // Check admin table if not found in students
        // You might have a separate admin table
        $adminSql = "SELECT id, username, email, password FROM admins WHERE email = :email";
        $adminStmt = $pdo->prepare($adminSql);
        $adminStmt->execute([':email' => $email]);
        $admin = $adminStmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            // Admin login
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['user_name'] = $admin['username'];
            $_SESSION['user_email'] = $admin['email'];
            $_SESSION['logged_in'] = true;
            $_SESSION['user_type'] = 'admin';
            
            echo json_encode([
                'success' => true,
                'message' => 'Admin login successful',
                'user' => [
                    'id' => $admin['id'],
                    'name' => $admin['username'],
                    'email' => $admin['email'],
                    'type' => 'admin'
                ],
                'redirect' => '/admin.html'
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
    }
    
} catch (Exception $e) {
    // Handle database errors
    error_log("Authentication error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Authentication error occurred'
    ]);
    exit;
}
?>
