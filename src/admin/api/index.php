<?php
/**
 * Student Management API - Main Entry Point
 * 
 * This is a RESTful API that handles all CRUD operations for student management.
 * It routes requests to the appropriate handler functions.
 * 
 * Endpoints:
 *   GET    /admin/api/                  - Get all students
 *   GET    /admin/api/?student_id=123   - Get single student
 *   POST   /admin/api/                  - Create new student
 *   PUT    /admin/api/                  - Update student
 *   DELETE /admin/api/?student_id=123   - Delete student
 *   POST   /admin/api/?action=change_password - Change password
 */

// ========== SESSION MANAGEMENT ==========
session_start();

// Store admin access timestamp in session
$_SESSION['admin_api_last_access'] = date('Y-m-d H:i:s');

// ========== HEADERS ==========
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400'); // 24 hours

// ========== PREFLIGHT HANDLING ==========
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ========== AUTHENTICATION CHECK ==========
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Please login first.',
        'session_status' => isset($_SESSION['logged_in']) ? 'logged_in: ' . $_SESSION['logged_in'] : 'not_set'
    ]);
    exit();
}

// Optional: Check admin role if you have role system
// if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
//     echo json_encode([
//         'success' => false,
//         'message' => 'Admin privileges required.'
//     ]);
//     exit();
// }

// ========== DATABASE CONNECTION ==========
require_once __DIR__ . '/../../../db.php';

try {
    if (!function_exists('getDatabaseConnection') && !isset($db)) {
        $host = 'localhost';
        $dbname = 'course_project';
        $username = 'root';
        $password = '';
        
        $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } elseif (function_exists('getDatabaseConnection')) {
        $db = getDatabaseConnection();
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

/**
 * Get all students or search for specific students
 */
function getStudents($db, $queryParams) {
    $search = isset($queryParams['search']) ? $queryParams['search'] : null;
    $sort = isset($queryParams['sort']) ? $queryParams['sort'] : null;
    $order = isset($queryParams['order']) ? $queryParams['order'] : 'asc';
    
    $sql = "SELECT id, student_id, name, email, created_at FROM students";
    $params = [];
    
    if ($search) {
        $sql .= " WHERE name LIKE :search OR student_id LIKE :search OR email LIKE :search";
        $params[':search'] = "%$search%";
    }
    
    if ($sort) {
        $allowedSortFields = ['name', 'student_id', 'email', 'created_at', 'id'];
        $allowedOrders = ['asc', 'desc'];
        
        if (in_array($sort, $allowedSortFields) && in_array(strtolower($order), $allowedOrders)) {
            $sql .= " ORDER BY $sort $order";
        }
    }
    
    try {
        $stmt = $db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendResponse([
            'success' => true,
            'data' => $students,
            'count' => count($students)
        ]);
    } catch (PDOException $e) {
        sendResponse([
            'success' => false,
            'message' => 'Failed to retrieve students',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Get a single student by student_id
 */
function getStudentById($db, $studentId) {
    $sql = "SELECT id, student_id, name, email, created_at FROM students WHERE student_id = :student_id";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':student_id', $studentId);
        $stmt->execute();
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($student) {
            sendResponse([
                'success' => true,
                'data' => $student
            ]);
        } else {
            sendResponse([
                'success' => false,
                'message' => 'Student not found'
            ], 404);
        }
    } catch (PDOException $e) {
        sendResponse([
            'success' => false,
            'message' => 'Failed to retrieve student',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Create a new student
 */
function createStudent($db, $data) {
    $requiredFields = ['student_id', 'name', 'email', 'password'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            sendResponse([
                'success' => false,
                'message' => "Missing required field: $field"
            ], 400);
            return;
        }
    }
    
    $student_id = sanitizeInput(trim($data['student_id']));
    $name = sanitizeInput(trim($data['name']));
    $email = sanitizeInput(trim($data['email']));
    $password = $data['password'];
    
    if (!validateEmail($email)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid email format'
        ], 400);
        return;
    }
    
    try {
        $checkSql = "SELECT student_id, email FROM students WHERE student_id = :student_id OR email = :email";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->bindParam(':student_id', $student_id);
        $checkStmt->bindParam(':email', $email);
        $checkStmt->execute();
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            $message = $existing['student_id'] === $student_id ? 
                'Student ID already exists' : 'Email already exists';
            sendResponse([
                'success' => false,
                'message' => $message
            ], 409);
            return;
        }
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO students (student_id, name, email, password, created_at) 
                VALUES (:student_id, :name, :email, :password, NOW())";
        $stmt = $db->prepare($sql);
        
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);
        
        $success = $stmt->execute();
        
        if ($success) {
            $studentData = [
                'student_id' => $student_id,
                'name' => $name,
                'email' => $email,
                'id' => $db->lastInsertId()
            ];
            sendResponse([
                'success' => true,
                'message' => 'Student created successfully',
                'data' => $studentData
            ], 201);
        } else {
            sendResponse([
                'success' => false,
                'message' => 'Failed to create student'
            ], 500);
        }
    } catch (PDOException $e) {
        sendResponse([
            'success' => false,
            'message' => 'Failed to create student',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Update an existing student
 */
function updateStudent($db, $data) {
    if (empty($data['student_id'])) {
        sendResponse([
            'success' => false,
            'message' => 'Student ID is required'
        ], 400);
        return;
    }
    
    $student_id = sanitizeInput(trim($data['student_id']));
    
    try {
        $checkSql = "SELECT id, email FROM students WHERE student_id = :student_id";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->bindParam(':student_id', $student_id);
        $checkStmt->execute();
        $currentStudent = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$currentStudent) {
            sendResponse([
                'success' => false,
                'message' => 'Student not found'
            ], 404);
            return;
        }
        
        $updates = [];
        $params = [':student_id' => $student_id];
        
        if (!empty($data['name'])) {
            $updates[] = 'name = :name';
            $params[':name'] = sanitizeInput(trim($data['name']));
        }
        
        if (!empty($data['email'])) {
            $email = sanitizeInput(trim($data['email']));
            if (!validateEmail($email)) {
                sendResponse([
                    'success' => false,
                    'message' => 'Invalid email format'
                ], 400);
                return;
            }
            
            if ($email !== $currentStudent['email']) {
                $emailCheckSql = "SELECT id FROM students WHERE email = :email AND student_id != :student_id";
                $emailCheckStmt = $db->prepare($emailCheckSql);
                $emailCheckStmt->bindParam(':email', $email);
                $emailCheckStmt->bindParam(':student_id', $student_id);
                $emailCheckStmt->execute();
                
                if ($emailCheckStmt->rowCount() > 0) {
                    sendResponse([
                        'success' => false,
                        'message' => 'Email already exists'
                    ], 409);
                    return;
                }
            }
            
            $updates[] = 'email = :email';
            $params[':email'] = $email;
        }
        
        if (empty($updates)) {
            sendResponse([
                'success' => false,
                'message' => 'No fields to update'
            ], 400);
            return;
        }
        
        $sql = "UPDATE students SET " . implode(', ', $updates) . " WHERE student_id = :student_id";
        $stmt = $db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $success = $stmt->execute();
        
        if ($success) {
            sendResponse([
                'success' => true,
                'message' => 'Student updated successfully'
            ]);
        } else {
            sendResponse([
                'success' => false,
                'message' => 'Failed to update student'
            ], 500);
        }
    } catch (PDOException $e) {
        sendResponse([
            'success' => false,
            'message' => 'Failed to update student',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Delete a student
 */
function deleteStudent($db, $studentId) {
    if (empty($studentId)) {
        sendResponse([
            'success' => false,
            'message' => 'Student ID is required'
        ], 400);
        return;
    }
    
    $studentId = sanitizeInput(trim($studentId));
    
    try {
        $checkSql = "SELECT id FROM students WHERE student_id = :student_id";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->bindParam(':student_id', $studentId);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            sendResponse([
                'success' => false,
                'message' => 'Student not found'
            ], 404);
            return;
        }
        
        $sql = "DELETE FROM students WHERE student_id = :student_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':student_id', $studentId);
        
        $success = $stmt->execute();
        
        if ($success) {
            sendResponse([
                'success' => true,
                'message' => 'Student deleted successfully'
            ]);
        } else {
            sendResponse([
                'success' => false,
                'message' => 'Failed to delete student'
            ], 500);
        }
    } catch (PDOException $e) {
        sendResponse([
            'success' => false,
            'message' => 'Failed to delete student',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Change password
 */
function changePassword($db, $data) {
    $requiredFields = ['student_id', 'current_password', 'new_password'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            sendResponse([
                'success' => false,
                'message' => "Missing required field: $field"
            ], 400);
            return;
        }
    }
    
    $student_id = sanitizeInput(trim($data['student_id']));
    $current_password = $data['current_password'];
    $new_password = $data['new_password'];
    
    if (strlen($new_password) < 8) {
        sendResponse([
            'success' => false,
            'message' => 'New password must be at least 8 characters long'
        ], 400);
        return;
    }
    
    try {
        $sql = "SELECT password FROM students WHERE student_id = :student_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            sendResponse([
                'success' => false,
                'message' => 'Student not found'
            ], 404);
            return;
        }
        
        $current_hash = $result['password'];
        
        if (!password_verify($current_password, $current_hash)) {
            sendResponse([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 401);
            return;
        }
        
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $updateSql = "UPDATE students SET password = :password WHERE student_id = :student_id";
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->bindParam(':password', $new_hash);
        $updateStmt->bindParam(':student_id', $student_id);
        
        $success = $updateStmt->execute();
        
        if ($success) {
            sendResponse([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);
        } else {
            sendResponse([
                'success' => false,
                'message' => 'Failed to change password'
            ], 500);
        }
    } catch (PDOException $e) {
        sendResponse([
            'success' => false,
            'message' => 'Failed to change password',
            'error' => $e->getMessage()
        ], 500);
    }
}

// ========== MAIN REQUEST ROUTER ==========
try {
    // Log admin action in session
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
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ], 500);
    
} catch (Exception $e) {
    error_log("General error in admin API: " . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'An error occurred',
        'error' => $e->getMessage()
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
