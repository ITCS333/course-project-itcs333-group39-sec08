<?php
/**
 * Course Resources API
 * 
 * This is a RESTful API that handles all CRUD operations for course resources 
 * and their associated comments/discussions.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: resources
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - title (VARCHAR(255))
 *   - description (TEXT)
 *   - link (VARCHAR(500))
 *   - created_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - resource_id (INT, FOREIGN KEY references resources.id)
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve resource(s) or comment(s)
 *   - POST: Create a new resource or comment
 *   - PUT: Update an existing resource
 *   - DELETE: Delete a resource or comment
 * 
 * Response Format: JSON
 * 
 * API Endpoints:
 *   Resources:
 *     GET    /api/resources.php                    - Get all resources
 *     GET    /api/resources.php?id={id}           - Get single resource by ID
 *     POST   /api/resources.php                    - Create new resource
 *     PUT    /api/resources.php                    - Update resource
 *     DELETE /api/resources.php?id={id}           - Delete resource
 * 
 *   Comments:
 *     GET    /api/resources.php?resource_id={id}&action=comments  - Get comments for resource
 *     POST   /api/resources.php?action=comment                    - Create new comment
 *     DELETE /api/resources.php?comment_id={id}&action=delete_comment - Delete comment
 */

// ============================================================================
// HEADERS AND INITIALIZATION
// ============================================================================
session_start();
$_SESSION['user_id'] = $_SESSION['user_id'] ?? null;
// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)

header("Content-Type:application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "API IS WORKING ✅";
exit;

// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
// Example: require_once '../config/Database.php';

require __DIR__ . '/../../../db.php';

// TODO: Get the PDO database connection
// Example: $database = new Database();
// Example: $db = $database->getConnection();

$db=$pdo;

// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']

$method = $_SERVER['REQUEST_METHOD'];

// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode() with associative array parameter

$input = file_get_contents('php://input');
$data = json_decode($input, true);

// TODO: Parse query parameters
// Get 'action', 'id', 'resource_id', 'comment_id' from $_GET

$action= $_GET['action']??null;
$id= $_GET['id']?? null;
$resource_id= $_GET['resource_id']??null;
$comment_id= $_GET['comment_id']??null;

// ============================================================================
// RESOURCE FUNCTIONS
// ============================================================================

/**
 * Function: Get all resources
 * Method: GET
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, created_at)
 *   - order: Optional sort order (asc or desc, default: desc)
 * 
 * Response:
 *   - success: true/false
 *   - data: Array of resource objects
 */
function getAllResources($db) {
    // TODO: Initialize the base SQL query
    // SELECT id, title, description, link, created_at FROM resources

    $sql = "SELECT id, title, description, link, created_at FROM resources";
    $params = [];

    // TODO: Check if search parameter exists
    // If yes, add WHERE clause using LIKE to search title and description
    // Use OR to search both fields

    if (!empty($_GET['search'])) {
        $sql .= " WHERE title LIKE :s OR description LIKE :s";
        $params[':s'] = "%" . $_GET['search'] . "%";
    }

    // TODO: Check if sort parameter exists and validate it
    // Only allow: title, created_at
    // Default to created_at if not provided or invalid

    $allowedSort = ['title', 'created_at'];
    $sort = $_GET['sort'] ?? 'created_at';
    if (!in_array($sort, $allowedSort, true)) {
        $sort = 'created_at';
    }
    
    // TODO: Check if order parameter exists and validate it
    // Only allow: asc, desc
    // Default to desc if not provided or invalid
    
    $order = strtolower($_GET['order'] ?? 'desc');
    if ($order !== 'asc' && $order !== 'desc') $order = 'desc';
    
    // TODO: Add ORDER BY clause to query

    $sql .= " ORDER BY $sort $order";

    // TODO: Prepare the SQL query using PDO
    
    $stmt = $db->prepare($sql);

    // TODO: If search parameter was used, bind the search parameter
    // Use % wildcards for LIKE search

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    // TODO: Execute the query
    
    $stmt->execute();

    // TODO: Fetch all results as an associative array

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TODO: Return JSON response with success status and data
    // Use the helper function sendResponse()

    sendResponse(["success" => true, "data" => $results], 200);

} 
 



/**
 * Function: Get a single resource by ID
 * Method: GET
 * 
 * Parameters:
 *   - $resourceId: The resource's database ID
 * 
 * Response:
 *   - success: true/false
 *   - data: Resource object or error message
 */
function getResourceById($db, $resourceId) {
    // TODO: Validate that resource ID is provided and is numeric
    // If not, return error response with 400 status

    if(!is_numeric($resourceId)||empty($resourceId)){
        http_response_code(400);
        echo json_encode([ "success" => false,
        "message" => "id not provided"]);
        return;
    }

    // TODO: Prepare SQL query to select resource by id
    // SELECT id, title, description, link, created_at FROM resources WHERE id = ?

    $sql = "SELECT id, title, description, link, created_at FROM resources WHERE id = ?";

    // TODO: Bind the resource_id parameter

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $resourceId);

    // TODO: Execute the query

    $stmt->execute();

    // TODO: Fetch the result as an associative array

    $resource = $stmt->fetch(PDO::FETCH_ASSOC);

    // TODO: Check if resource exists
    // If yes, return success response with resource data
    // If no, return error response with 404 status
    if ($resource) {
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "data" => $resource
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Resource not found"
        ]);
    }
}


/**
 * Function: Create a new resource
 * Method: POST
 * 
 * Required JSON Body:
 *   - title: Resource title (required)
 *   - description: Resource description (optional)
 *   - link: URL to the resource (required)
 * 
 * Response:
 *   - success: true/false
 *   - message: Success or error message
 *   - id: ID of created resource (on success)
 */
function createResource($db, $data) {
    // TODO: Validate required fields
    // Check if title and link are provided and not empty
    // If any required field is missing, return error response with 400 status
    if(!isset($data['title'])||!isset($data['link'])||empty($data['title'])||empty($data['link'])){
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Missing required fields: title and link are required"
        ]);
        return;
    }
    
    // TODO: Sanitize input data
    // Trim whitespace from all fields
    // Validate URL format for link using filter_var with FILTER_VALIDATE_URL
    // If URL is invalid, return error response with 400 status

    $title = trim($data['title']);
    $link  = trim($data['link']);
    $description = isset($data['description']) ? trim($data['description']) : "";

    if (!filter_var($link, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid URL format"
        ]);
        return;
    }
    
    // TODO: Set default value for description if not provided
    // Use empty string as default

    if ($description === null) {
        $description = "";
    }
    // TODO: Prepare INSERT query
    // INSERT INTO resources (title, description, link) VALUES (?, ?, ?)

    $sql = "INSERT INTO resources (title, description, link) VALUES (?, ?, ?)";

    // TODO: Bind parameters
    // Bind title, description, and link
    $stmt = $db->prepare($sql);

    $stmt->bindValue(1, $title);
    $stmt->bindValue(2, $description);
    $stmt->bindValue(3, $link);

    // TODO: Execute the query
    
    $ok = $stmt->execute();

    // TODO: Check if insert was successful
    // If yes, get the last inserted ID using $db->lastInsertId()
    // Return success response with 201 status and the new resource ID
    // If no, return error response with 500 status
    if ($ok) {
        $newId = $db->lastInsertId();
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Resource created successfully",
            "id" => $newId
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to create resource"
        ]);
    }
}


/**
 * Function: Update an existing resource
 * Method: PUT
 * 
 * Required JSON Body:
 *   - id: The resource's database ID (required)
 *   - title: Updated resource title (optional)
 *   - description: Updated description (optional)
 *   - link: Updated URL (optional)
 * 
 * Response:
 *   - success: true/false
 *   - message: Success or error message
 */
function updateResource($db, $data) {
    // TODO: Validate that resource ID is provided
    // If not, return error response with 400 status
     
    if (!isset($data['id']) || empty($data['id']) || !is_numeric($data['id'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Resource ID is required"
        ]);
        return;
    }

    // TODO: Check if resource exists
    // Prepare and execute a SELECT query to find the resource by id
    // If not found, return error response with 404 status
    
    $checkSql = "SELECT id FROM resources WHERE id = :id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':id', $id);
    $checkStmt->execute();
    $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$exists) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Resource not found"
        ]);
        return;
    }

    // TODO: Build UPDATE query dynamically based on provided fields
    // Initialize empty arrays for fields to update and values
    // Check which fields are provided (title, description, link)
    // Add each provided field to the update arrays
    
    $fields = [];
    $params = [];

    if (isset($data['title']) && trim($data['title']) !== '') {
        $fields[] = "title = :title";
        $params[':title'] = trim($data['title']);
    }

    if (isset($data['description'])) {
        $fields[] = "description = :description";
        $params[':description'] = trim($data['description']);
    }

    if (isset($data['link']) && trim($data['link']) !== '') {
        $link = trim($data['link']);
    }

    // TODO: If no fields to update, return error response with 400 status
    
    if (count($fields) === 0) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "No fields provided to update"
        ]);
        return;
    }

    // TODO: If link is being updated, validate URL format
    // Use filter_var with FILTER_VALIDATE_URL
    // If invalid, return error response with 400 status
    
    if (isset($data['link']) && trim($data['link']) !== '') {
        $link = trim($data['link']);
    
        if (!filter_var($link, FILTER_VALIDATE_URL)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Invalid URL format"]);
            return;
        }
    
        $fields[] = "link = :link";
        $params[':link'] = $link;
    }

    // TODO: Build the complete UPDATE SQL query
    // UPDATE resources SET field1 = ?, field2 = ? WHERE id = ?
    
    $sql = "UPDATE resources SET " . implode(", ", $fields) . " WHERE id = :?";

    // TODO: Prepare the query
    
    $stmt = $db->prepare($sql);

    // TODO: Bind parameters dynamically
    // Bind all update values, then bind the resource ID at the end

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':id', $id);

    // TODO: Execute the query
    
    $ok = $stmt->execute();

    // TODO: Check if update was successful
    // If yes, return success response with 200 status
    // If no, return error response with 500 status

    if ($ok) {
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Resource updated successfully"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to update resource"
        ]);
    }
}


/**
 * Function: Delete a resource
 * Method: DELETE
 * 
 * Parameters:
 *   - $resourceId: The resource's database ID
 * 
 * Response:
 *   - success: true/false
 *   - message: Success or error message
 * 
 * Note: This should also delete all associated comments
 */
function deleteResource($db, $resourceId) {
    // TODO: Validate that resource ID is provided and is numeric
    // If not, return error response with 400 status
    
    if (empty($resourceId) || !is_numeric($resourceId)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid resource ID"
        ]);
        return;
    }

    // TODO: Check if resource exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    
    $checkSql = "SELECT id FROM resources WHERE id = :id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':id', (int)$resourceId);
    $checkStmt->execute();
    $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$exists) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Resource not found"
        ]);
        return;
    }

    // TODO: Begin a transaction (for data integrity)
    // Use $db->beginTransaction()
    
    $db->beginTransaction();

    try {
        // TODO: First, delete all associated comments
        // Prepare DELETE query for comments table
        // DELETE FROM comments WHERE resource_id = ?
        
        $delCommentsSql = "DELETE FROM comments_resource WHERE resource_id = :rid";
        $delCommentsStmt = $db->prepare($delCommentsSql);

        // TODO: Bind resource_id and execute
        
        $delCommentsStmt->bindValue(':rid',$resourceId);
        $delCommentsStmt->execute();

        // TODO: Then, delete the resource
        // Prepare DELETE query for resources table
        // DELETE FROM resources WHERE id = ?
        
        $delResourceSql = "DELETE FROM resources WHERE id = :id";
        $delResourceStmt = $db->prepare($delResourceSql);

        // TODO: Bind resource_id and execute
        
        $delResourceStmt->bindValue(':id',$resourceId);
        $delResourceStmt->execute();

        // TODO: Commit the transaction
        // Use $db->commit()
        
        $db->commit();

        // TODO: Return success response with 200 status
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Resource deleted successfully"
        ]);
        return;

    } catch (Exception $e) {
        // TODO: Rollback the transaction on error
        // Use $db->rollBack()
        
        $db->rollBack();

        // TODO: Return error response with 500 status

        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to delete resource"
        ]);
        return;

    }
}


// ============================================================================
// COMMENT FUNCTIONS
// ============================================================================

/**
 * Function: Get all comments for a specific resource
 * Method: GET with action=comments
 * 
 * Query Parameters:
 *   - resource_id: The resource's database ID (required)
 * 
 * Response:
 *   - success: true/false
 *   - data: Array of comment objects
 */
function getCommentsByResourceId($db, $resourceId) {
    // TODO: Validate that resource_id is provided and is numeric
    // If not, return error response with 400 status
    
    if (empty($resourceId) || !is_numeric($resourceId)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid resource_id"
        ]);
        return;
    }

    // TODO: Prepare SQL query to select comments for the resource
    // SELECT id, resource_id, author, text, created_at 
    // FROM comments 
    // WHERE resource_id = ? 
    // ORDER BY created_at ASC
    
    $sql = "SELECT id, resource_id, author, text, created_at  FROM comments_resource WHERE resource_id = :? ORDER BY created_at ASC";

    // TODO: Bind the resource_id paramete

    $stmt = $db->prepare($sql);
    $stmt->bindValue(1, $resourceId);

    // TODO: Execute the query

    $stmt->execute();

    // TODO: Fetch all results as an associative array
    
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TODO: Return success response with comments data
    // Even if no comments exist, return empty array (not an error)
   
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "data" => $comments
    ]);

}


/**
 * Function: Create a new comment
 * Method: POST with action=comment
 * 
 * Required JSON Body:
 *   - resource_id: The resource's database ID (required)
 *   - author: Name of the comment author (required)
 *   - text: Comment text content (required)
 * 
 * Response:
 *   - success: true/false
 *   - message: Success or error message
 *   - id: ID of created comment (on success)
 */
function createComment($db, $data) {
    // TODO: Validate required fields
    // Check if resource_id, author, and text are provided and not empty
    // If any required field is missing, return error response with 400 status
    
    if (empty($data['resource_id']) || empty($data['author']) || empty($data['text'])) {
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }

    // TODO: Validate that resource_id is numeric
    // If not, return error response with 400 status
    
    if (!is_numeric($data['resource_id'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid resource_id"
        ]);
        return;
    }

    $resourceId = $data['resource_id'];

    // TODO: Check if the resource exists
    // Prepare and execute SELECT query on resources table
    // If resource not found, return error response with 404 status
    
    $checkSql = "SELECT id FROM resources WHERE id = :id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':id', $resourceId);
    $checkStmt->execute();
    $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$exists) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Resource not found"
        ]);
        return;
    }

    // TODO: Sanitize input data
    // Trim whitespace from author and text
    
    $author = trim($data['author']);
    $text   = trim($data['text']);

    // TODO: Prepare INSERT query
    // INSERT INTO comments (resource_id, author, text) VALUES (?, ?, ?)
    
    $sql = "INSERT INTO comments_resource (resource_id, author, text)
    VALUES (?, ?, ?)";

    // TODO: Bind parameters
    // Bind resource_id, author, and text
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(1, $resourceId);
    $stmt->bindValue(2, $author);
    $stmt->bindValue(3, $text);

    // TODO: Execute the query
    
    $ok = $stmt->execute();

    // TODO: Check if insert was successful
    // If yes, get the last inserted ID using $db->lastInsertId()
    // Return success response with 201 status and the new comment ID
    // If no, return error response with 500 status

    if ($ok) {
        $newId = $db->lastInsertId();
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Comment created successfully",
            "id" => $newId
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to create comment"
        ]);
    }

}


/**
 * Function: Delete a comment
 * Method: DELETE with action=delete_comment
 * 
 * Query Parameters or JSON Body:
 *   - comment_id: The comment's database ID (required)
 * 
 * Response:
 *   - success: true/false
 *   - message: Success or error message
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that comment_id is provided and is numeric
    // If not, return error response with 400 status
    
    if (empty($commentId) || !is_numeric($commentId)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid comment_id"
        ]);
        return;
    }

    // TODO: Check if comment exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    
    $checkSql = "SELECT id FROM comments_resource WHERE id = :id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':id', $commentId);
    $checkStmt->execute();
    $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$exists) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Comment not found"
        ]);
        return;
    }

    // TODO: Prepare DELETE query
    // DELETE FROM comments WHERE id = ?
    
    $sql = "DELETE FROM comments_resource WHERE id = ?";

    // TODO: Bind the comment_id parameter
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(1, $commentId);

    // TODO: Execute the query
    
    $ok = $stmt->execute();

    // TODO: Check if delete was successful
    // If yes, return success response with 200 status
    // If no, return error response with 500 status

    if ($ok) {
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Comment deleted successfully"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to delete comment"
        ]);
    }

}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Route the request based on HTTP method and action parameter
    
    if ($method === 'GET') {
        // TODO: Check the action parameter to determine which function to call
        
        // If action is 'comments', get comments for a resource
        // TODO: Check if action === 'comments'
        // Get resource_id from query parameters
        // Call getCommentsByResourceId()
        
        // If id parameter exists, get single resource
        // TODO: Check if 'id' parameter exists in $_GET
        // Call getResourceById()

        if ($action === 'comments') {
            getCommentsByResourceId($db, $resource_id);
        }
        
        elseif (!empty($id)) {
            getResourceById($db, $id);
        }

        // Otherwise, get all resources
        // TODO: Call getAllResources()
        
        else {
            getAllResources($db);
        }

    } elseif ($method === 'POST') {
        // TODO: Check the action parameter to determine which function to call
        
        // If action is 'comment', create a new comment
        // TODO: Check if action === 'comment'
        // Call createComment()

        if ($action === 'comment') {
            createComment($db, $data);
        }
        // Otherwise, create a new resource
        // TODO: Call createResource()
        
        else {
            createResource($db, $data);
        }

    } elseif ($method === 'PUT') {
        // TODO: Update a resource
        // Call updateResource()

        updateResource($db, $data);

    } elseif ($method === 'DELETE') {
        // TODO: Check the action parameter to determine which function to call
        
        // If action is 'delete_comment', delete a comment
        // TODO: Check if action === 'delete_comment'
        // Get comment_id from query parameters or request body
        // Call deleteComment()

        if ($action === 'delete_comment') {
            $cid = $comment_id ?? ($data['comment_id'] ?? null);
            deleteComment($db, $cid);
        }

        // Otherwise, delete a resource
        // TODO: Get resource id from query parameter or request body
        // Call deleteResource()
        else {
            $rid = $id ?? ($data['id'] ?? null);
            deleteResource($db, $rid);
        }
        
    } else {
        // TODO: Return error for unsupported methods
        // Set HTTP status to 405 (Method Not Allowed)
        // Return JSON error message using sendResponse()

        sendResponse(["success" => false, "message" => "Method Not Allowed"], 405);

    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    // Log the error message (optional, use error_log())
    // Return generic error response with 500 status
    // Do NOT expose detailed error messages to the client in production
    
    error_log($e->getMessage());
    sendResponse(["success" => false, "message" => "Database error"], 500);


} catch (Exception $e) {
    // TODO: Handle general errors
    // Log the error message (optional)
    // Return error response with 500 status

    error_log($e->getMessage());
    sendResponse(["success" => false, "message" => "Server error"], 500);

}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param array $data - Data to send (should include 'success' key)
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code using http_response_code()
    
    http_response_code($statusCode);

    // TODO: Ensure data is an array
    // If not, wrap it in an array
    
    if (!is_array($data)) {
        $data = [$data];
    }

    // TODO: Echo JSON encoded data
    // Use JSON_PRETTY_PRINT for readability (optional)
    
    echo json_encode($data, JSON_PRETTY_PRINT);

    // TODO: Exit to prevent further execution
    exit;
}


/**
 * Helper function to validate URL format
 * 
 * @param string $url - URL to validate
 * @return bool - True if valid, false otherwise
 */
function validateUrl($url) {
    // TODO: Use filter_var with FILTER_VALIDATE_URL
    // Return true if valid, false otherwise

 return filter_var($url, FILTER_VALIDATE_URL) !== false;

}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace using trim()
    
    $data = trim($data);

    // TODO: Strip HTML tags using strip_tags()
    
    $data = strip_tags($data);

    // TODO: Convert special characters using htmlspecialchars()
    // Use ENT_QUOTES to escape both double and single quotes
    
    $data = htmlspecialchars($data, ENT_QUOTES);

    // TODO: Return sanitized data

    return $data;

}


/**
 * Helper function to validate required fields
 * 
 * @param array $data - Data array to validate
 * @param array $requiredFields - Array of required field names
 * @return array - Array with 'valid' (bool) and 'missing' (array of missing fields)
 */
function validateRequiredFields($data, $requiredFields) {
    // TODO: Initialize empty array for missing fields
    
    $missing = [];

    // TODO: Loop through required fields
    // Check if each field exists in data and is not empty
    // If missing or empty, add to missing fields array
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missing[] = $field;
        }
    }

    // TODO: Return result array
    // ['valid' => (count($missing) === 0), 'missing' => $missing]

    return [
        'valid' => (count($missing) === 0),
        'missing' => $missing
    ];

}

?>
