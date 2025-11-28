<?php
/**
 * Assignment Management API
 * 
 * This is a RESTful API that handles all CRUD operations for course assignments
 * and their associated discussion comments.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: assignments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - title (VARCHAR(200))
 *   - description (TEXT)
 *   - due_date (DATE)
 *   - files (TEXT)
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - assignment_id (VARCHAR(50), FOREIGN KEY)
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve assignment(s) or comment(s)
 *   - POST: Create a new assignment or comment
 *   - PUT: Update an existing assignment
 *   - DELETE: Delete an assignment or comment
 * 
 * Response Format: JSON
 */

// ============================================================================
// HEADERS AND CORS CONFIGURATION
// ============================================================================

// TODO: Set Content-Type header to application/json
header("Content-Type: application/json");


// TODO: Set CORS headers to allow cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}



// ============================================================================
// DATABASE CONNECTION
// ============================================================================

// TODO: Include the database connection class
//include 'includes/db.php';
include '../../../includes/db.php';

//require_once '../../../includes/db.php';

// TODO: Create database connection
$db = $pdo;

// TODO: Set PDO to throw exceptions on errors
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


// ============================================================================
// REQUEST PARSING
// ============================================================================

// TODO: Get the HTTP request method
$method = $_SERVER['REQUEST_METHOD'];


// TODO: Get the request body for POST and PUT requests
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// TODO: Parse query parameters
$query = $_GET;



// ============================================================================
// ASSIGNMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Function: Get all assignments
 * Method: GET
 * Endpoint: ?resource=assignments
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, due_date, created_at)
 *   - order: Optional sort order (asc or desc, default: asc)
 * 
 * Response: JSON array of assignment objects
 */
function getAllAssignments($db) {
    // TODO: Start building the SQL query
    $sql = "SELECT id, title, description, due_date, files, created_at, updated_at FROM assignments";
    $params = [];

  
    // TODO: Check if 'search' query parameter exists in $_GET
    if (!empty($_GET['search'])) {
        $sql .= " WHERE title LIKE :s OR description LIKE :s";
        $params[':s'] = "%" . $_GET['search'] . "%";
    }
    
    // TODO: Check if 'sort' and 'order' query parameters exist
    $sort = "";
    if (!empty($_GET['sort'])) {
        $sort = $_GET['sort'];
    }
    
    $order = "asc";
    if (!empty($_GET['order'])) {
        $order = $_GET['order'];
    }
    
    if ($sort !== "") {
        // Validate sort and order parameters
        $allowedSortFields = ['title', 'due_date', 'created_at'];
        $allowedOrderValues = ['asc', 'desc'];

        if (!in_array($sort, $allowedSortFields)) {
            $sort = 'created_at'; // Default sort field
        }

        if (!in_array(strtolower($order), $allowedOrderValues)) {
            $order = 'asc'; // Default order
        }

        $sql .= " ORDER BY " . $sort . " " . strtoupper($order);
    }
    
    // TODO: Prepare the SQL statement using $db->prepare()
    $stmt = $db->prepare($sql);

    // TODO: Bind parameters if search is used
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    // TODO: Execute the prepared statement
    $stmt->execute();
    
    // TODO: Fetch all results as associative array
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // TODO: For each assignment, decode the 'files' field from JSON to array
    foreach ($rows as &$item) {
        if (!empty($item['files'])) {
            $item['files'] = json_decode($item['files'], true);
        } else {
            $item['files'] = [];
        }
    }

    // TODO: Return JSON response
    echo json_encode($rows);
}


/**
 * Function: Get a single assignment by ID
 * Method: GET
 * Endpoint: ?resource=assignments&id={assignment_id}
 * 
 * Query Parameters:
 *   - id: The assignment ID (required)
 * 
 * Response: JSON object with assignment details
 */
function getAssignmentById($db, $assignmentId) {
   

    // TODO: Validate that $assignmentId is provided and not empty
    if (empty($assignmentId)) {
        echo json_encode(['error' => 'No assignment id provided']);
        return;
    }
    // TODO: Prepare SQL query to select assignment by id
   
    $sql = "SELECT id, title, description, due_date, files, created_at, updated_at 
    FROM assignments 
    WHERE id = :id";
    
    // TODO: Bind the :id parameter
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);


    // TODO: Execute the statement
    $stmt->execute();
    // TODO: Fetch the result as associative array
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    // TODO: Check if assignment was found
    if (!$row) {
        echo json_encode(['error' => 'Assignment not found']);
        return;
    }
    
    // TODO: Decode the 'files' field from JSON to array
    if (!empty($row['files'])) {
        $row['files'] = json_decode($row['files'], true);
    } else {
        $row['files'] = [];
    }
    
    // TODO: Return success response with assignment data
    echo json_encode($row);

}


/**
 * Function: Create a new assignment
 * Method: POST
 * Endpoint: ?resource=assignments
 * 
 * Required JSON Body:
 *   - title: Assignment title (required)
 *   - description: Assignment description (required)
 *   - due_date: Due date in YYYY-MM-DD format (required)
 *   - files: Array of file URLs/paths (optional)
 * 
 * Response: JSON object with created assignment data
 */
function createAssignment($db, $data) {
     // TODO: Validate required fields
     if (empty($data['title']) || empty($data['description']) || empty($data['due_date'])) {
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }

    // TODO: Sanitize input data
    $title       = trim($data['title']);
    $description = trim($data['description']);
    $due_date    = trim($data['due_date']);

    // TODO: Validate due_date format
    if (!validateDate($due_date)) {
        echo json_encode(['error' => 'Invalid date format']);
        return;
    }

    // TODO: Generate a unique assignment ID
    // (Handled by AUTO_INCREMENT in DB)

    // TODO: Handle the 'files' field
    $files = [];
    if (!empty($data['files']) && is_array($data['files'])) {
        $files = $data['files'];
    }
    $filesJson = json_encode($files);

    // TODO: Prepare INSERT query
    $sql = "INSERT INTO assignments (title, description, due_date, files, created_at, updated_at)
            VALUES (:title, :description, :due_date, :files, NOW(), NOW())";

    // TODO: Bind all parameters
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':title', $title);
    $stmt->bindValue(':description', $description);
    $stmt->bindValue(':due_date', $due_date);
    $stmt->bindValue(':files', $filesJson);

    // TODO: Execute the statement
    $ok = $stmt->execute();

    // TODO: Check if insert was successful
    if ($ok) {
        $newId = $db->lastInsertId();
        echo json_encode([
            'id'          => $newId,
            'title'       => $title,
            'description' => $description,
            'due_date'    => $due_date,
            'files'       => $files
        ]);
        return;
    }

    // TODO: If insert failed, return 500 error
    echo json_encode(['error' => 'Failed to create assignment']);
}


/**
 * Function: Update an existing assignment
 * Method: PUT
 * Endpoint: ?resource=assignments
 * 
 * Required JSON Body:
 *   - id: Assignment ID (required, to identify which assignment to update)
 *   - title: Updated title (optional)
 *   - description: Updated description (optional)
 *   - due_date: Updated due date (optional)
 *   - files: Updated files array (optional)
 * 
 * Response: JSON object with success status
 */
function updateAssignment($db, $data) {
     // TODO: Validate that 'id' is provided in $data
     if (empty($data['id'])) {
        echo json_encode(['error' => 'Missing assignment id']);
        return;
    }

    // TODO: Store assignment ID in variable
    $assignmentId = $data['id'];

    // TODO: Check if assignment exists
    $checkQuery = "SELECT id FROM assignments WHERE id = :id";
    $checkStatement = $db->prepare($checkQuery);
    $checkStatement->bindValue(':id', $assignmentId);
    $checkStatement->execute();
    $existingAssignment = $checkStatement->fetch(PDO::FETCH_ASSOC);

    if (!$existingAssignment) {
        echo json_encode(['error' => 'Assignment not found']);
        return;
    }

    // TODO: Build UPDATE query dynamically based on provided fields
    $updateQuery = "UPDATE assignments SET ";
    $updateFields = [];
    $updateParameters = [];

    // TODO: Check which fields are provided and add to SET clause
    if (isset($data['title'])) {
        $updateFields[] = "title = :title";
        $updateParameters[':title'] = trim($data['title']);
    }

    if (isset($data['description'])) {
        $updateFields[] = "description = :description";
        $updateParameters[':description'] = trim($data['description']);
    }

    if (isset($data['due_date']) && $data['due_date'] !== "") {
        $cleanDueDate = trim($data['due_date']);

        // Validate format
        if (!validateDate($cleanDueDate)) {
            echo json_encode(['error' => 'Invalid date format']);
            return;
        }

        $updateFields[] = "due_date = :due_date";
        $updateParameters[':due_date'] = $cleanDueDate;
    }

    if (isset($data['files'])) {
        $encodedFiles = json_encode($data['files']);
        $updateFields[] = "files = :files";
        $updateParameters[':files'] = $encodedFiles;
    }

    // TODO: If no fields to update, return 400 error
    if (count($updateFields) === 0) {
        echo json_encode(['error' => 'No fields to update']);
        return;
    }

    // TODO: Complete the UPDATE query
    $updateFields[] = "updated_at = NOW()";
    $updateQuery .= implode(", ", $updateFields) . " WHERE id = :id";

    // TODO: Prepare the statement
    $updateStatement = $db->prepare($updateQuery);

    // TODO: Bind all parameters dynamically
    foreach ($updateParameters as $key => $value) {
        $updateStatement->bindValue($key, $value);
    }
    $updateStatement->bindValue(':id', $assignmentId);

    // TODO: Execute the statement
    $executionResult = $updateStatement->execute();

    // TODO: Check if update was successful
    if (!$executionResult) {
        echo json_encode(['error' => 'Failed to update assignment']);
        return;
    }

    // TODO: If no rows affected, return appropriate message
    if ($updateStatement->rowCount() === 0) {
        echo json_encode(['message' => 'No changes were made']);
        return;
    }

    echo json_encode(['success' => true]);
    
}


/**
 * Function: Delete an assignment
 * Method: DELETE
 * Endpoint: ?resource=assignments&id={assignment_id}
 * 
 * Query Parameters:
 *   - id: Assignment ID (required)
 * 
 * Response: JSON object with success status
 */
function deleteAssignment($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    if (empty($assignmentId)) {
        echo json_encode(['error' => 'No assignment id provided']);
        return;
    }

    // TODO: Check if assignment exists
    $checkSql = "SELECT id FROM assignments WHERE id = :id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':id', $assignmentId);
    $checkStmt->execute();
    $found = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$found) {
        echo json_encode(['error' => 'Assignment not found']);
        return;
    }

    // TODO: Delete associated comments first (due to foreign key constraint)
    $delComSql = "DELETE FROM comments WHERE assignment_id = :id";
    $delComStmt = $db->prepare($delComSql);
    $delComStmt->bindValue(':id', $assignmentId);
    $delComStmt->execute();

    // TODO: Prepare DELETE query for assignment
    $delSql = "DELETE FROM assignments WHERE id = :id";

    // TODO: Bind the :id parameter
    $delStmt = $db->prepare($delSql);
    $delStmt->bindValue(':id', $assignmentId);

    // TODO: Execute the statement
    $done = $delStmt->execute();

    // TODO: Check if delete was successful
    if (!$done) {
        // TODO: If delete failed, return 500 error
        echo json_encode(['error' => 'Failed to delete assignment']);
        return;
    }

    if ($delStmt->rowCount() === 0) {
        echo json_encode(['error' => 'No assignment deleted']);
        return;
    }

    echo json_encode(['success' => true]);
}


// ============================================================================
// COMMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Function: Get all comments for a specific assignment
 * Method: GET
 * Endpoint: ?resource=comments&assignment_id={assignment_id}
 * 
 * Query Parameters:
 *   - assignment_id: The assignment ID (required)
 * 
 * Response: JSON array of comment objects
 */
function getCommentsByAssignment($db, $assignmentId) {
     // TODO: Validate that $assignmentId is provided and not empty
     if (empty($assignmentId)) {
        echo json_encode(['error' => 'Missing assignment id']);
        return;
    }

    // TODO: Prepare SQL query to select all comments for the assignment
    $sql = "SELECT id, assignment_id, author, text, created_at 
            FROM comments 
            WHERE assignment_id = :assignment_id
            ORDER BY created_at DESC";

    // TODO: Bind the :assignment_id parameter
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':assignment_id', $assignmentId);

    // TODO: Execute the statement
    $stmt->execute();

    // TODO: Fetch all results as associative array
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TODO: Return success response with comments data
    echo json_encode($comments);
    
}


/**
 * Function: Create a new comment
 * Method: POST
 * Endpoint: ?resource=comments
 * 
 * Required JSON Body:
 *   - assignment_id: Assignment ID (required)
 *   - author: Comment author name (required)
 *   - text: Comment content (required)
 * 
 * Response: JSON object with created comment data
 */
function createComment($db, $data) {
    // TODO: Validate required fields
    if (empty($data['assignment_id']) || empty($data['author']) || empty($data['text'])) {
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }

    // TODO: Sanitize input data
    $assignmentId = trim($data['assignment_id']);
    $author = trim($data['author']);
    $text = trim($data['text']);

    // TODO: Validate that text is not empty after trimming
    if ($text === "") {
        echo json_encode(['error' => 'Comment text cannot be empty']);
        return;
    }

    // TODO: Verify that the assignment exists
    $checkSql = "SELECT id FROM assignments WHERE id = :id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':id', $assignmentId);
    $checkStmt->execute();
    $found = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$found) {
        echo json_encode(['error' => 'Assignment not found']);
        return;
    }

    // TODO: Prepare INSERT query for comment
    $sql = "INSERT INTO comments (assignment_id, author, text, created_at)
            VALUES (:assignment_id, :author, :text, NOW())";

    // TODO: Bind all parameters
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':assignment_id', $assignmentId);
    $stmt->bindValue(':author', $author);
    $stmt->bindValue(':text', $text);

    // TODO: Execute the statement
    $ok = $stmt->execute();

    if (!$ok) {
        echo json_encode(['error' => 'Failed to create comment']);
        return;
    }

    // TODO: Get the ID of the inserted comment
    $commentId = $db->lastInsertId();

    // TODO: Return success response with created comment data
    echo json_encode([
        'id' => $commentId,
        'assignment_id' => $assignmentId,
        'author' => $author,
        'text' => $text
    ]);
    
}


/**
 * Function: Delete a comment
 * Method: DELETE
 * Endpoint: ?resource=comments&id={comment_id}
 * 
 * Query Parameters:
 *   - id: Comment ID (required)
 * 
 * Response: JSON object with success status
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that $commentId is provided and not empty
    if (empty($commentId)) {
        echo json_encode(['error' => 'Missing comment id']);
        return;
    }

    // TODO: Check if comment exists
    $checkSql = "SELECT id FROM comments WHERE id = :id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':id', $commentId);
    $checkStmt->execute();
    $found = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$found) {
        echo json_encode(['error' => 'Comment not found']);
        return;
    }

    // TODO: Prepare DELETE query
    $sql = "DELETE FROM comments WHERE id = :id";

    // TODO: Bind the :id parameter
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $commentId);

    // TODO: Execute the statement
    $ex = $stmt->execute();

    // TODO: Check if delete was successful
    if (!$ex) {
        // TODO: If delete failed, return 500 error
        echo json_encode(['error' => 'Failed to delete comment']);
        return;
    }

    echo json_encode(['success' => true]);
    
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
     // Get the 'resource' query parameter to determine which resource to access
     $resource = isset($query['resource']) ? $query['resource'] : null;

     // Route based on HTTP method and resource type
     if ($method === 'GET') {
         // Handle GET requests
         if ($resource === 'assignments') {
             // Check if 'id' query parameter exists
             if (isset($query['id'])) {
                 getAssignmentById($db, $query['id']);
             } else {
                 getAllAssignments($db);
             }
 
         } elseif ($resource === 'comments') {
             // Check if 'assignment_id' query parameter exists
             if (isset($query['assignment_id'])) {
                 getCommentsByAssignment($db, $query['assignment_id']);
             } else {
                 sendResponse(['error' => 'Missing assignment_id'], 400);
             }
 
         } else {
             // Invalid resource, return 400 error
             sendResponse(['error' => 'Invalid resource'], 400);
         }
 
     } elseif ($method === 'POST') {
         // Handle POST requests (create operations)
         if ($resource === 'assignments') {
             createAssignment($db, $data);
 
         } elseif ($resource === 'comments') {
             createComment($db, $data);
 
         } else {
             // Invalid resource, return 400 error
             sendResponse(['error' => 'Invalid resource'], 400);
         }
 
     } elseif ($method === 'PUT') {
         // Handle PUT requests (update operations)
         if ($resource === 'assignments') {
             updateAssignment($db, $data);
         } else {
             // PUT not supported for other resources
             sendResponse(['error' => 'PUT not supported for this resource'], 400);
         }
 
     } elseif ($method === 'DELETE') {
         // Handle DELETE requests
         if ($resource === 'assignments') {
             // Get 'id' from query parameter
             if (isset($query['id'])) {
                 deleteAssignment($db, $query['id']);
             } else {
                 sendResponse(['error' => 'Missing assignment id'], 400);
             }
 
         } elseif ($resource === 'comments') {
             // Get comment 'id' from query parameter
             if (isset($query['id'])) {
                 deleteComment($db, $query['id']);
             } else {
                 sendResponse(['error' => 'Missing comment id'], 400);
             }
 
         } else {
             // Invalid resource, return 400 error
             sendResponse(['error' => 'Invalid resource'], 400);
         }
 
     } else {
         // Method not supported
         sendResponse(['error' => 'Method not supported'], 405);
     }
 
 } catch (PDOException $e) {
     // Handle database errors
     sendResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
 
 } catch (Exception $e) {
     // Handle general errors
     sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
 }


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response and exit
 * 
 * @param array $data - Data to send as JSON
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {

    // TODO: Set HTTP response code
    http_response_code($statusCode);

    // TODO: Ensure data is an array
    if (!is_array($data)) {
        $data = ['message' => $data];
    }

    // TODO: Echo JSON encoded data
    echo json_encode($data);

    // TODO: Exit to prevent further execution
    exit;
    
}


/**
 * Helper function to sanitize string input
 * 
 * @param string $data - Input data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
       // TODO: Trim whitespace from beginning and end
    $data = trim($data);

    // TODO: Remove HTML and PHP tags
    $data = strip_tags($data);

    // TODO: Convert special characters to HTML entities
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

    // TODO: Return the sanitized data
    return $data;
    
}


/**
 * Helper function to validate date format (YYYY-MM-DD)
 * 
 * @param string $date - Date string to validate
 * @return bool - True if valid, false otherwise
 */
function validateDate($date) {
    // TODO: Use DateTime::createFromFormat to validate
    $d = DateTime::createFromFormat('Y-m-d', $date);

    // TODO: Return true if valid, false otherwise
    return $d && $d->format('Y-m-d') === $date;
}


/**
 * Helper function to validate allowed values (for sort fields, order, etc.)
 * 
 * @param string $value - Value to validate
 * @param array $allowedValues - Array of allowed values
 * @return bool - True if valid, false otherwise
 */
function validateAllowedValue($value, $allowedValues) {
    return in_array($value, $allowedValues);
    
}

?>
