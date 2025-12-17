<?php
/**
 * Student Management API - Main Entry Point
 * 
 * This is a RESTful API that handles all CRUD operations for student management.
 */

// ========== SESSION MANAGEMENT ==========
session_start();

// ========== HEADERS ==========
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');

// ========== PREFLIGHT HANDLING ==========
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ========== AUTHENTICATION CHECK ==========
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    sendResponse([
        'success' => false,
        'message' => 'Unauthorized access. Please login first.'
    ], 401);
    exit();
}

// Store admin access timestamp
$_SESSION['admin_api_last_access'] = date('Y-m-d H:i:s');

// ========== DATABASE CONNECTION ==========
// Adjust the path based on your directory structure
$dbPath = __DIR__ . '/../../../../includes/db.php';

if (!file_exists($dbPath)) {
    sendResponse([
        'success' => false,
        'message' => 'Database configuration not found'
    ], 500);
    exit();
}

require_once $dbPath;

try {
    // Assuming your Database class is in db.php
    if (class_exists('Database')) {
        $database = new Database();
        $db = $database->getConnection();
    } else {
        // Fallback to direct PDO connection
        $host = 'localhost';
        $dbname = 'course_project';
        $username = 'root';
        $password = '';
        
        $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    sendResponse([
        'success' => false,
        'message' => 'Database connection failed',
        'error' => $e->getMessage()
    ], 500);
    exit();
}

// ========== REQUEST PARSING ==========
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
if ($input === null && $method !== 'GET' && $method !== 'DELETE') {
    $input = [];
}
$queryParams = $_GET;

// ========== FUNCTION DEFINITIONS ==========
// [Keep all your existing functions: getStudents, getStudentById, createStudent, 
//  updateStudent, deleteStudent, changePassword]
// ... (all your functions remain the same)

// ========== MAIN REQUEST ROUTER ==========
try {
    // Log admin action
    $_SESSION['last_admin_action'] = [
        'method' => $method,
        'timestamp' => date('Y-m-d H:i:s'),
        'query_params' => $queryParams
    ];
    
    if ($method === 'GET') {
        if (isset($queryParams['student_id'])) {
            getStudentById($db, $queryParams['student_id']);
        } else {
            getStudents($db, $queryParams);
        }
        
    } elseif ($method === 'POST') {
        if (isset($queryParams['action']) && $queryParams['action'] === 'change_password') {
            changePassword($db, $input);
        } else {
            createStudent($db, $input);
        }
        
    } elseif ($method === 'PUT') {
        updateStudent($db, $input);
        
    } elseif ($method === 'DELETE') {
        $studentId = isset($queryParams['student_id']) ? $queryParams['student_id'] : 
                    (isset($input['student_id']) ? $input['student_id'] : null);
        deleteStudent($db, $studentId);
        
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Method not allowed. Supported methods: GET, POST, PUT, DELETE'
        ], 405);
    }
    
} catch (PDOException $e) {
    error_log("Database error in admin API: " . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'Database error occurred'
    ], 500);
    
} catch (Exception $e) {
    error_log("General error in admin API: " . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'An error occurred'
    ], 500);
}

// ========== HELPER FUNCTIONS ==========

/**
 * Send JSON response
 */
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit();
}

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitize input
 */
function sanitizeInput($data) {
    if (is_string($data)) {
        $data = trim($data);
        $data = strip_tags($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    return $data;
}
?>
