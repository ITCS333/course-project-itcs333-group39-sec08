<?php
// Save as src/auth/login.php
// Simple test version - returns success for testing

header('Content-Type: application/json');

// Simulate processing delay
sleep(1);

// Test data - accepts any email/password combination for testing
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Simple validation
if (empty($email) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Email and password are required'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email format'
    ]);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode([
        'success' => false,
        'message' => 'Password must be at least 8 characters'
    ]);
    exit;
}

// Always return success for testing purposes
echo json_encode([
    'success' => true,
    'message' => 'Login successful',
    'user' => [
        'id' => 1,
        'name' => 'Test User',
        'email' => $email
    ]
]);
?>
