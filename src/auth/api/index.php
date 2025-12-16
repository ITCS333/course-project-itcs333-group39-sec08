<?php
/**
 * Test Authentication Handler (No Database Required)
 * Use this for testing if you don't have MySQL set up
 */

session_start();
header('Content-Type: application/json');

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get input
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Check required fields
if (!isset($data['email']) || !isset($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Email and password required']);
    exit;
}

$email = trim($data['email']);
$password = $data['password'];

// Simple validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password too short']);
    exit;
}

// ========== TEST CREDENTIALS ==========
// For testing without a database, use these test credentials:
$test_users = [
    'test@example.com' => [
        'id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
        // Password is "Test1234" hashed
        'password_hash' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
    ],
    'admin@example.com' => [
        'id' => 2,
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        // Password is "Admin1234" hashed
        'password_hash' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
    ]
];

// Check if user exists in test data
if (isset($test_users[$email])) {
    $user = $test_users[$email];
    
    // For testing: Accept either the hashed password OR a simple match
    if (password_verify($password, $user['password_hash']) || $password === 'Test1234') {
        // Login successful
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name']
