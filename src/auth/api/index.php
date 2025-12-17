<?php
// src/auth/api/index.php
header('Content-Type: application/json');

// Simple test response
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

if (!isset($data['email']) || !isset($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Email and password required']);
    exit;
}

// Simple validation
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

// Return success for testing
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
