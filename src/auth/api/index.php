<?php
header('Content-Type: application/json');

// Get JSON data from request
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
