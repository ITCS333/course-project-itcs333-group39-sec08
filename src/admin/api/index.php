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

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400'); // 24 hours

// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// TODO: Include the database connection
// Based on your project structure, include the root db.php
require_once __DIR__ . '/../../../db.php';

// TODO: Get the PDO database connection
// Assuming db.php provides a database connection
// Adjust this based on your actual db.php structure
try {
    if (!function_exists('getDatabaseConnection') && !isset($db)) {
        // If neither function nor variable exists, create a default connection
        // You'll need to adjust these credentials
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
    // If $db is already set by db.php, use it as is
} catch (Exception $e) {
    sendResponse([
        'success' => false,
        'message' => 'Database connection failed',
        'error' => $e->getMessage()
    ], 500);
    exit();
}

// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method = $_SERVER['REQUEST_METHOD'];

// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()
$input = json_decode(file_get_contents('php://input'), true);
if ($input === null && $method !== 'GET' && $method !== 'DELETE') {
    $input = [];
}

// TODO: Parse query parameters for filtering and searching
$queryParams = $_GET;


/**
 * Function: Get all students or search for specific students
 * Method: GET
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by name, student_id, or email
 *   - sort: Optional field to sort by (name, student_id, email)
 *   - order: Optional sort order (asc or desc)
 */
function getStudents($db, $queryParams) {
    // TODO: Check if search parameter exists
    // If yes, prepare SQL query with WHERE clause using LIKE
    // Search should work on name, student_id, and email fields
    $search = isset($queryParams['search']) ? $queryParams['search'] : null;
    $sort = isset($queryParams['sort']) ? $queryParams['sort'] : null;
    $order = isset($queryParams['order']) ? $queryParams['order'] : 'asc';
    
    $sql = "SELECT id, student_id, name, email, created_at FROM students";
    $params = [];
    
    if ($search) {
        $sql .= " WHERE name LIKE :search OR student_id LIKE :search OR email LIKE :search";
        $params[':search'] = "%$search%";
    }
    
    // TODO: Check if sort and order parameters exist
    // If yes, add ORDER BY clause to the query
    // Validate sort field to prevent SQL injection (only allow: name, student_id, email)
    // Validate order to prevent SQL injection (only allow: asc, desc)
    if ($sort) {
        $allowedSortFields = ['name', 'student_id', 'email', 'created_at', 'id'];
        $allowedOrders = ['asc', 'desc'];
        
        if (in_array($sort, $allowedSortFields) && in_array(strtolower($order), $allowedOrders)) {
            $sql .= " ORDER BY $sort $order";
        }
    }
    
    try {
        // TODO: Prepare the SQL query using PDO
        // Note: Do NOT select the password field
        $stmt = $db->prepare($sql);
        
        // TODO: Bind parameters if using search
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        // TODO: Execute the query
        $stmt->execute();
        
        // TODO: Fetch all results as an associative array
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // TODO: Return JSON response with success status and data
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
 * Function: Get a single student by student_id
 * Method: GET
 * 
 * Query Parameters:
 *   - student_id: The student's university ID
 */
function getStudentById($db, $studentId) {
    // TODO: Prepare SQL query to select student by student_id
    $sql = "SELECT id, student_id, name, email, created_at FROM students WHERE student_id = :student_id";
    
    try {
        $stmt = $db->prepare($sql);
        
        // TODO: Bind the student_id parameter
        $stmt->bindParam(':student_id', $studentId);
        
        // TODO: Execute the query
        $stmt->execute();
        
        // TODO: Fetch the result
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // TODO: Check if student exists
        // If yes, return success response with student data
        // If no, return error response with 404 status
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
 * Function: Create a new student
 * Method: POST
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (must be unique)
 *   - name: Student's full name
 *   - email: Student's email (must be unique)
 *   - password: Default password (will be hashed)
 */
function createStudent($db, $data) {
    // TODO: Validate required fields
    // Check if student_id, name, email, and password are provided
    // If any field is missing, return error response with 400 status
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
    
    // TODO: Sanitize input data
    // Trim whitespace from all fields
    // Validate email format using filter_var()
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
        // TODO: Check if student_id or email already exists
        // Prepare and execute a SELECT query to check for duplicates
        // If duplicate found, return error response with 409 status (Conflict)
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
        
        // TODO: Hash the password
        // Use password_hash() with PASSWORD_DEFAULT
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // TODO: Prepare INSERT query
        $sql = "INSERT INTO students (student_id, name, email, password, created_at) 
                VALUES (:student_id, :name, :email, :password, NOW())";
        $stmt = $db->prepare($sql);
        
        // TODO: Bind parameters
        // Bind student_id, name, email, and hashed password
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);
        
        // TODO: Execute the query
        $success = $stmt->execute();
        
        // TODO: Check if insert was successful
        // If yes, return success response with 201 status (Created)
        // If no, return error response with 500 status
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
 * Function: Update an existing student
 * Method: PUT
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (to identify which student to update)
 *   - name: Updated student name (optional)
 *   - email: Updated student email (optional)
 */
function updateStudent($db, $data) {
    // TODO: Validate that student_id is provided
    // If not, return error response with 400 status
    if (empty($data['student_id'])) {
        sendResponse([
            'success' => false,
            'message' => 'Student ID is required'
        ], 400);
        return;
    }
    
    $student_id = sanitizeInput(trim($data['student_id']));
    
    try {
        // TODO: Check if student exists
        // Prepare and execute a SELECT query to find the student
        // If not found, return error response with 404 status
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
        
        // TODO: Build UPDATE query dynamically based on provided fields
        // Only update fields that are provided in the request
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
            
            // TODO: If email is being updated, check if new email already exists
            // Prepare and execute a SELECT query
            // Exclude the current student from the check
            // If duplicate found, return error response with 409 status
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
        
        // TODO: Bind parameters dynamically
        // Bind only the parameters that are being updated
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        // TODO: Execute the query
        $success = $stmt->execute();
        
        // TODO: Check if update was successful
        // If yes, return success response
        // If no, return error response with 500 status
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
 * Function: Delete a student
 * Method: DELETE
 * 
 * Query Parameters or JSON Body:
 *   - student_id: The student's university ID
 */
function deleteStudent($db, $studentId) {
    // TODO: Validate that student_id is provided
    // If not, return error response with 400 status
    if (empty($studentId)) {
        sendResponse([
            'success' => false,
            'message' => 'Student ID is required'
        ], 400);
        return;
    }
    
    $studentId = sanitizeInput(trim($studentId));
    
    try {
        // TODO: Check if student exists
        // Prepare and execute a SELECT query
        // If not found, return error response with 404 status
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
        
        // TODO: Prepare DELETE query
        $sql = "DELETE FROM students WHERE student_id = :student_id";
        $stmt = $db->prepare($sql);
        
        // TODO: Bind the student_id parameter
        $stmt->bindParam(':student_id', $studentId);
        
        // TODO: Execute the query
        $success = $stmt->execute();
        
        // TODO: Check if delete was successful
        // If yes, return success response
        // If no, return error response with 500 status
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
 * Function: Change password
 * Method: POST with action=change_password
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (identifies whose password to change)
 *   - current_password: The student's current password
 *   - new_password: The new password to set
 */
function changePassword($db, $data) {
    // TODO: Validate required fields
    // Check if student_id, current_password, and new_password are provided
    // If any field is missing, return error response with 400 status
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
    
    // TODO: Validate new password strength
    // Check minimum length (at least 8 characters)
    // If validation fails, return error response with 400 status
    if (strlen($new_password) < 8) {
        sendResponse([
            'success' => false,
            'message' => 'New password must be at least 8 characters long'
        ], 400);
        return;
    }
    
    try {
        // TODO: Retrieve current password hash from database
        // Prepare and execute SELECT query to get password
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
        
        // TODO: Verify current password
        // Use password_verify() to check if current_password matches the hash
        // If verification fails, return error response with 401 status (Unauthorized)
        if (!password_verify($current_password, $current_hash)) {
            sendResponse([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 401);
            return;
        }
        
        // TODO: Hash the new password
        // Use password_hash() with PASSWORD_DEFAULT
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        // TODO: Update password in database
        // Prepare UPDATE query
        $updateSql = "UPDATE students SET password = :password WHERE student_id = :student_id";
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->bindParam(':password', $new_hash);
        $updateStmt->bindParam(':student_id', $student_id);
        
        // TODO: Bind parameters and execute
        $success = $updateStmt->execute();
        
        // TODO: Check if update was successful
        // If yes, return success response
        // If no, return error response with 500 status
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


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Route the request based on HTTP method
    
    if ($method === 'GET') {
        // TODO: Check if student_id is provided in query parameters
        // If yes, call getStudentById()
        // If no, call getStudents() to get all students (with optional search/sort)
        if (isset($queryParams['student_id'])) {
            getStudentById($db, $queryParams['student_id']);
        } else {
            getStudents($db, $queryParams);
        }
        
    } elseif ($method === 'POST') {
        // TODO: Check if this is a change password request
        // Look for action=change_password in query parameters
        // If yes, call changePassword()
        // If no, call createStudent()
        if (isset($queryParams['action']) && $queryParams['action'] === 'change_password') {
            changePassword($db, $input);
        } else {
            createStudent($db, $input);
        }
        
    } elseif ($method === 'PUT') {
        // TODO: Call updateStudent()
        updateStudent($db, $input);
        
    } elseif ($method === 'DELETE') {
        // TODO: Get student_id from query parameter or request body
        // Call deleteStudent()
        $studentId = isset($queryParams['student_id']) ? $queryParams['student_id'] : 
                    (isset($input['student_id']) ? $input['student_id'] : null);
        deleteStudent($db, $studentId);
        
    } else {
        // TODO: Return error for unsupported methods
        // Set HTTP status to 405 (Method Not Allowed)
        // Return JSON error message
        sendResponse([
            'success' => false,
            'message' => 'Method not allowed. Supported methods: GET, POST, PUT, DELETE'
        ], 405);
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    // Log the error message (optional)
    // Return generic error response with 500 status
    error_log("Database error in admin API: " . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ], 500);
    
} catch (Exception $e) {
    // TODO: Handle general errors
    // Return error response with 500 status
    error_log("General error in admin API: " . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'An error occurred',
        'error' => $e->getMessage()
    ], 500);
}


// ============================================================================
// HELPER FUNCTIONS (Optional but Recommended)
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param mixed $data - Data to send
 * @param int $statusCode - HTTP status code
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    http_response_code($statusCode);
    
    // TODO: Echo JSON encoded data
    echo json_encode($data, JSON_PRETTY_PRINT);
    
    // TODO: Exit to prevent further execution
    exit();
}


/**
 * Helper function to validate email format
 * 
 * @param string $email - Email address to validate
 * @return bool - True if valid, false otherwise
 */
function validateEmail($email) {
    // TODO: Use filter_var with FILTER_VALIDATE_EMAIL
    // Return true if valid, false otherwise
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace
    // TODO: Strip HTML tags using strip_tags()
    // TODO: Convert special characters using htmlspecialchars()
    // Return sanitized data
    if (is_string($data)) {
        $data = trim($data);
        $data = strip_tags($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    return $data;
}

?>
