<?php
    /**
     * Student Management API - Main Entry Point
     * 
     * This is a RESTful API that handles all CRUD operations for student management.
     */

    // ========== SESSION MANAGEMENT ==========
    session_start();

    // التحقق من أن المستخدم مسجل الدخول وهو أدمن
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized access. Admin privileges required.']);
        exit;
    }




// ============================================================================
// DATABASE CONNECTION
// ============================================================================
try {
    require_once __DIR__ . '/../../../../includes/db.php';
    $database = new Database();
    $db = $database->getConnection();


} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}
    // ========== HELPER FUNCTIONS ==========
    function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit();
    }

    function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    function sanitizeInput($data) {
        if (is_string($data)) {
            $data = trim($data);
            $data = strip_tags($data);
            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }
        return $data;
    }

    // ========== HEADERS ==========
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400');

    // ========== PREFLIGHT HANDLING ==========
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    // Store admin access timestamp
    $_SESSION['admin_api_last_access'] = date('Y-m-d H:i:s');

// ================== DB CONNECTION ==================
require_once __DIR__ . '/../../../../includes/db.php';

try {
    $database = new Database();
    $db = $database->getConnection();  


    $pdo = $db;

} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}


    // ========== REQUEST PARSING ==========
    $method = $_SERVER['REQUEST_METHOD'];

    // Read JSON body for POST/PUT/DELETE (GET normally uses query params)
    $inputRaw = file_get_contents('php://input');
    $input = json_decode($inputRaw, true);

    if (!is_array($input)) {
        $input = [];
    }

    $queryParams = $_GET;

    // ========== FUNCTION DEFINITIONS ==========

    function getStudents($db, $queryParams) {
        try {
            $search = isset($queryParams['search']) ? $queryParams['search'] : '';
            $page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;
            $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : 50;
            $offset = ($page - 1) * $limit;

            if ($search) {
                $searchTerm = '%' . $search . '%';
                $sql = "SELECT id, id as student_id, name, email FROM users 
                        WHERE is_admin = 0 AND (name LIKE :search OR id LIKE :search OR email LIKE :search) 
                        ORDER BY name LIMIT :limit OFFSET :offset";
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();

                $countSql = "SELECT COUNT(*) as total FROM users 
                             WHERE is_admin = 0 AND (name LIKE :search OR id LIKE :search OR email LIKE :search)";
                $countStmt = $db->prepare($countSql);
                $countStmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
                $countStmt->execute();
                $totalResult = $countStmt->fetch(PDO::FETCH_ASSOC);
                $total = $totalResult['total'];
            } else {
                $sql = "SELECT id, id as student_id, name, email FROM users WHERE is_admin = 0 ORDER BY name LIMIT :limit OFFSET :offset";
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();

                $countStmt = $db->query("SELECT COUNT(*) as total FROM users WHERE is_admin = 0");
                $totalResult = $countStmt->fetch(PDO::FETCH_ASSOC);
                $total = $totalResult['total'];
            }

            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

            sendResponse([
                'success' => true,
                'data' => $students,
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ]);

        } catch (PDOException $e) {
            error_log("Error in getStudents: " . $e->getMessage());
            sendResponse([
                'success' => false,
                'message' => 'Database error'
            ], 500);
        }
    }

    function getStudentById($db, $studentId) {
        try {
            $sql = "SELECT id, id as student_id, name, email FROM users WHERE (id = :id OR id = :student_id) AND is_admin = 0";
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $studentId, ':student_id' => $studentId]);
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
            error_log("Error in getStudentById: " . $e->getMessage());
            sendResponse([
                'success' => false,
                'message' => 'Database error'
            ], 500);
        }
    }

    function createStudent($db, $input) {
        try {
            if (!isset($input['name']) || !isset($input['student_id']) || !isset($input['email']) || !isset($input['password'])) {
                sendResponse([
                    'success' => false,
                    'message' => 'All fields are required: name, student_id, email, password'
                ], 400);
            }

            $name = sanitizeInput($input['name']);
            $email = sanitizeInput($input['email']);

            if (!validateEmail($email)) {
                sendResponse([
                    'success' => false,
                    'message' => 'Invalid email format'
                ], 400);
            }

            $password_hash = password_hash($input['password'], PASSWORD_DEFAULT);

            $checkSql = "SELECT id FROM users WHERE email = :email";
            $checkStmt = $db->prepare($checkSql);
            $checkStmt->execute([':email' => $email]);

            if ($checkStmt->fetch()) {
                sendResponse([
                    'success' => false,
                    'message' => 'Email already exists'
                ], 409);
            }

            $sql = "INSERT INTO users (name, email, password, is_admin) 
                    VALUES (:name, :email, :password, 0)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':password' => $password_hash
            ]);

            sendResponse([
                'success' => true,
                'message' => 'Student created successfully',
                'id' => $db->lastInsertId()
            ], 201);

        } catch (PDOException $e) {
            error_log("Error in createStudent: " . $e->getMessage());
            sendResponse([
                'success' => false,
                'message' => 'Database error'
            ], 500);
        }
    }

    function updateStudent($db, $input) {
        try {
            if (!isset($input['student_id'])) {
                sendResponse([
                    'success' => false,
                    'message' => 'Student ID is required'
                ], 400);
            }

            $student_id = sanitizeInput($input['student_id']);
            $updates = [];
            $params = [':student_id' => $student_id];

            if (isset($input['name'])) {
                $updates[] = 'name = :name';
                $params[':name'] = sanitizeInput($input['name']);
            }

            if (isset($input['email'])) {
                if (!validateEmail($input['email'])) {
                    sendResponse([
                        'success' => false,
                        'message' => 'Invalid email format'
                    ], 400);
                }
                $updates[] = 'email = :email';
                $params[':email'] = sanitizeInput($input['email']);
            }

            if (isset($input['password']) && !empty($input['password'])) {
                $updates[] = 'password = :password';
                $params[':password'] = password_hash($input['password'], PASSWORD_DEFAULT);
            }

            if (empty($updates)) {
                sendResponse([
                    'success' => false,
                    'message' => 'No fields to update'
                ], 400);
            }

            if (isset($input['email'])) {
                $checkSql = "SELECT id FROM users WHERE email = :email AND id != :student_id AND is_admin = 0";
                $checkStmt = $db->prepare($checkSql);
                $checkStmt->execute([':email' => $params[':email'], ':student_id' => $student_id]);

                if ($checkStmt->fetch()) {
                    sendResponse([
                        'success' => false,
                        'message' => 'Email already exists for another student'
                    ], 409);
                }
            }

            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :student_id AND is_admin = 0";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            if ($stmt->rowCount() > 0) {
                sendResponse([
                    'success' => true,
                    'message' => 'Student updated successfully'
                ]);
            } else {
                sendResponse([
                    'success' => false,
                    'message' => 'Student not found'
                ], 404);
            }

        } catch (PDOException $e) {
            error_log("Error in updateStudent: " . $e->getMessage());
            sendResponse([
                'success' => false,
                'message' => 'Database error'
            ], 500);
        }
    }

    function deleteStudent($db, $studentId) {
        try {
            if (!$studentId) {
                sendResponse([
                    'success' => false,
                    'message' => 'Student ID is required'
                ], 400);
            }

            $sql = "DELETE FROM users WHERE id = :id AND is_admin = 0";
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $studentId]);

            if ($stmt->rowCount() > 0) {
                sendResponse([
                    'success' => true,
                    'message' => 'Student deleted successfully'
                ]);
            } else {
                sendResponse([
                    'success' => false,
                    'message' => 'Student not found'
                ], 404);
            }

        } catch (PDOException $e) {
            error_log("Error in deleteStudent: " . $e->getMessage());
            sendResponse([
                'success' => false,
                'message' => 'Database error'
            ], 500);
        }
    }

    function changePassword($db, $input) {
        try {
            // هذا لتغيير كلمة مرور الأدمن نفسه
            if (!isset($_SESSION['user_id'])) {
                sendResponse([
                    'success' => false,
                    'message' => 'Session expired'
                ], 401);
            }

            if (!isset($input['current_password']) || !isset($input['new_password'])) {
                sendResponse([
                    'success' => false,
                    'message' => 'Current password and new password are required'
                ], 400);
            }

            $adminId = $_SESSION['user_id'];
            $currentPassword = $input['current_password'];
            $newPassword = $input['new_password'];

            // التحقق من كلمة المرور الحالية
            $sql = "SELECT password FROM admins WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $adminId]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$admin || !password_verify($currentPassword, $admin['password'])) {
                sendResponse([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ], 400);
            }

            // تحديث كلمة المرور الجديدة
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateSql = "UPDATE admins SET password = :password WHERE id = :id";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute([':password' => $newPasswordHash, ':id' => $adminId]);

            sendResponse([
                'success' => true,
                'message' => 'Password updated successfully'
            ]);

        } catch (PDOException $e) {
            error_log("Error in changePassword: " . $e->getMessage());
            sendResponse([
                'success' => false,
                'message' => 'Database error'
            ], 500);
        }
    }

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
            $studentId =
                $queryParams['student_id'] ??
                ($input['student_id'] ?? null);

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
?>
