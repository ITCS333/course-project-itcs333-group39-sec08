<?php
/**
 * Discussion Board API
 * 
 * This is a RESTful API that handles all CRUD operations for the discussion board.
 * It manages both discussion topics and their replies.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: topics
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - topic_id (VARCHAR(50), UNIQUE) - The topic's unique identifier (e.g., "topic_1234567890")
 *   - subject (VARCHAR(255)) - The topic subject/title
 *   - message (TEXT) - The main topic message
 *   - author (VARCHAR(100)) - The author's name
 *   - created_at (TIMESTAMP) - When the topic was created
 * 
 * Table: replies
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - reply_id (VARCHAR(50), UNIQUE) - The reply's unique identifier (e.g., "reply_1234567890")
 *   - topic_id (VARCHAR(50)) - Foreign key to topics.topic_id
 *   - text (TEXT) - The reply message
 *   - author (VARCHAR(100)) - The reply author's name
 *   - created_at (TIMESTAMP) - When the reply was created
 * 
 * API Endpoints:
 * 
 * Topics:
 *   GET    /api/discussion.php?resource=topics              - Get all topics (with optional search)
 *   GET    /api/discussion.php?resource=topics&id={id}      - Get single topic
 *   POST   /api/discussion.php?resource=topics              - Create new topic
 *   PUT    /api/discussion.php?resource=topics              - Update a topic
 *   DELETE /api/discussion.php?resource=topics&id={id}      - Delete a topic
 * 
 * Replies:
 *   GET    /api/discussion.php?resource=replies&topic_id={id} - Get all replies for a topic
 *   POST   /api/discussion.php?resource=replies              - Create new reply
 *   DELETE /api/discussion.php?resource=replies&id={id}      - Delete a reply
 * 
 * Response Format: JSON
 */

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
require_once __DIR__ . "/Database.php";



// TODO: Get the PDO database connection
// $db = $database->getConnection();
$database = new Database();
$db = $database->getConnection();

// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method = $_SERVER['REQUEST_METHOD'];


// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);


// TODO: Parse query parameters for filtering and searching
$resource  = $_GET['resource'] ?? null;
$id        = $_GET['id'] ?? null;
$topicId   = $_GET['topic_id'] ?? null;
$search    = $_GET['search'] ?? null;

// ============================================================================
// TOPICS FUNCTIONS
// ============================================================================

/**
 * Function: Get all topics or search for specific topics
 * Method: GET
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by subject, message, or author
 *   - sort: Optional field to sort by (subject, author, created_at)
 *   - order: Optional sort order (asc or desc, default: desc)
 */
function getAllTopics($db) {
    // TODO: Initialize base SQL query
    // Select topic_id, subject, message, author, and created_at (formatted as date)
     $sql = "SELECT topic_id, subject, message, author, DATE(created_at) AS created_at FROM topics";
    
    // TODO: Initialize an array to hold bound parameters
    $params = [];
    
    // TODO: Check if search parameter exists in $_GET
    // If yes, add WHERE clause using LIKE for subject, message, OR author
    // Add the search term to the params array
    if (!empty($_GET['search'])) {
        $sql .= " WHERE subject LIKE :search OR message LIKE :search OR author LIKE :search";
        $params[':search'] = "%" . $_GET['search'] . "%";
    }

    
    // TODO: Add ORDER BY clause
    $allowedSort = ["subject", "author", "created_at"];
    $allowedOrder = ["asc", "desc"];
    $sort  = $_GET['sort']  ?? "created_at";
    $order = $_GET['order'] ?? "desc";

    // Check for sort and order parameters in $_GET
    if (!in_array($sort, $allowedSort)) {
        $sort = "created_at";
    }
    if (!in_array($order, $allowedOrder)) {
        $order = "desc";
    }
    $sql .= " ORDER BY $sort $order";
    // Validate the sort field (only allow: subject, author, created_at)
    // Validate order (only allow: asc, desc)
    // Default to ordering by created_at DESC
    
    // TODO: Prepare the SQL statement
    $stmt = $db->prepare($sql);
    
    // TODO: Bind parameters if search was used
    // Loop through $params array and bind each parameter
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Fetch all results as an associative array
    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // TODO: Return JSON response with success status and data
    // Call sendResponse() helper function or echo json_encode directly
    echo json_encode([
        "success" => true,
        "data" => $topics
    ]);
}


/**
 * Function: Get a single topic by topic_id
 * Method: GET
 * 
 * Query Parameters:
 *   - id: The topic's unique identifier
 */
function getTopicById($db, $topicId) {
    // TODO: Validate that topicId is provided
    // If empty, return error with 400 status
    if (empty($topicId)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Topic ID is required."
        ]);
        return;
    }
    
    // TODO: Prepare SQL query to select topic by topic_id
    // Select topic_id, subject, message, author, and created_at
    $sql = "SELECT topic_id, subject, message, author, DATE(created_at) AS created_at 
            FROM topics 
            WHERE topic_id = :topic_id";
    
    // TODO: Prepare and bind the topic_id parameter
    $stmt = $db->prepare($sql);
    $stmt->bindValue(":topic_id", $topicId);
    // TODO: Execute the query
    $stmt->execute();
    // TODO: Fetch the result
     $topic = $stmt->fetch(PDO::FETCH_ASSOC);

    // TODO: Check if topic exists
    // If topic found, return success response with topic data
    // If not found, return error with 404 status
    if ($topic) {
        echo json_encode([
            "success" => true,
            "data" => $topic
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Topic not found."
        ]);
    }
}


/**
 * Function: Create a new topic
 * Method: POST
 * 
 * Required JSON Body:
 *   - topic_id: Unique identifier (e.g., "topic_1234567890")
 *   - subject: Topic subject/title
 *   - message: Main topic message
 *   - author: Author's name
 */
function createTopic($db, $data) {
    // TODO: Validate required fields
    // Check if topic_id, subject, message, and author are provided
    // If any required field is missing, return error with 400 status
    if (
        empty($data['topic_id']) ||
        empty($data['subject']) ||
        empty($data['message']) ||
        empty($data['author'])
    ) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Missing required fields: topic_id, subject, message, author."
        ]);
        return;
    }
    
    // TODO: Sanitize input data
    // Trim whitespace from all string fields
    // Use the sanitizeInput() helper function
    $topic_id = sanitizeInput($data['topic_id']);
    $subject  = sanitizeInput($data['subject']);
    $message  = sanitizeInput($data['message']);
    $author   = sanitizeInput($data['author']);

    
    // TODO: Check if topic_id already exists
    // Prepare and execute a SELECT query to check for duplicate
    // If duplicate found, return error with 409 status (Conflict)
    $checkSql = "SELECT topic_id FROM topics WHERE topic_id = :topic_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(":topic_id", $topic_id);
    $checkStmt->execute();

    if ($checkStmt->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(409); // Conflict
        echo json_encode([
            "success" => false,
            "message" => "Topic ID already exists."
        ]);
        return;
    }
    
    // TODO: Prepare INSERT query
    // Insert topic_id, subject, message, and author
    // The created_at field should auto-populate with CURRENT_TIMESTAMP
    $sql = "INSERT INTO topics (topic_id, subject, message, author)
            VALUES (:topic_id, :subject, :message, :author)";

    
    // TODO: Prepare the statement and bind parameters
    // Bind all the sanitized values
    $stmt = $db->prepare($sql);
    $stmt->bindValue(":topic_id", $topic_id);
    $stmt->bindValue(":subject", $subject);
    $stmt->bindValue(":message", $message);
    $stmt->bindValue(":author", $author);
    
    // TODO: Execute the query
    $success = $stmt->execute();
    
    // TODO: Check if insert was successful
    // If yes, return success response with 201 status (Created)
    // Include the topic_id in the response
    // If no, return error with 500 status
    if ($success) {
        http_response_code(201); // Created
        echo json_encode([
            "success" => true,
            "message" => "Topic created successfully.",
            "topic_id" => $topic_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to create topic."
        ]);
    }
}


/**
 * Function: Update an existing topic
 * Method: PUT
 * 
 * Required JSON Body:
 *   - topic_id: The topic's unique identifier
 *   - subject: Updated subject (optional)
 *   - message: Updated message (optional)
 */
function updateTopic($db, $data) {
    // TODO: Validate that topic_id is provided
    // If not provided, return error with 400 status
    if (empty($data['topic_id'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "topic_id is required."
        ]);
        return;
    }

    $topic_id = sanitizeInput($data['topic_id']);

    
    // TODO: Check if topic exists
    // Prepare and execute a SELECT query
    // If not found, return error with 404 status
    $checkSql = "SELECT topic_id FROM topics WHERE topic_id = :topic_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(":topic_id", $topic_id);
    $checkStmt->execute();

    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Topic not found."
        ]);
        return;
    }

    
    // TODO: Build UPDATE query dynamically based on provided fields
    // Only update fields that are provided in the request
    $updates = [];
    $params  = [":topic_id" => $topic_id];

    if (!empty($data['subject'])) {
        $updates[] = "subject = :subject";
        $params[":subject"] = sanitizeInput($data['subject']);
    }

    if (!empty($data['message'])) {
        $updates[] = "message = :message";
        $params[":message"] = sanitizeInput($data['message']);
    }

    // TODO: Check if there are any fields to update
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "No fields provided to update."
        ]);
        return;
    }

    
    // TODO: Check if there are any fields to update
    // If $updates array is empty, return error
    
    
    // TODO: Complete the UPDATE query
    $sql = "UPDATE topics SET " . implode(", ", $updates) . " WHERE topic_id = :topic_id";
    // TODO: Prepare statement and bind parameters
    // Bind all parameters from the $params array
    $stmt = $db->prepare($sql);

    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    
    // TODO: Execute the query
    $success = $stmt->execute();
    // TODO: Check if update was successful
    if ($success) {
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                "success" => true,
                "message" => "Topic updated successfully."
            ]);
        } else {
            echo json_encode([
                "success" => true,
                "message" => "No changes made (fields may be identical)."
            ]);
        } } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to update topic."
        ]);
    }
    // If yes, return success response
    // If no rows affected, return appropriate message
    // If error, return error with 500 status
}


/**
 * Function: Delete a topic
 * Method: DELETE
 * 
 * Query Parameters:
 *   - id: The topic's unique identifier
 */
function deleteTopic($db, $topicId) {
    // TODO: Validate that topicId is provided
    // If not, return error with 400 status
    if (empty($topicId)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Topic ID is required."
        ]);
        return;
    }
       $topicId = sanitizeInput($topicId);
    // TODO: Check if topic exists
    // Prepare and execute a SELECT query
    // If not found, return error with 404 status
    $checkSql = "SELECT topic_id FROM topics WHERE topic_id = :topic_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(":topic_id", $topicId);
    $checkStmt->execute();

    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Topic not found."
        ]);
        return;
    }
    
    // TODO: Delete associated replies first (foreign key constraint)
    // Prepare DELETE query for replies table
    $deleteRepliesSql = "DELETE FROM replies WHERE topic_id = :topic_id";
    $deleteRepliesStmt = $db->prepare($deleteRepliesSql);
    $deleteRepliesStmt->bindValue(":topic_id", $topicId);
    $deleteRepliesStmt->execute();

    
    // TODO: Prepare DELETE query for the topic
    $deleteTopicSql = "DELETE FROM topics WHERE topic_id = :topic_id";
    $deleteTopicStmt = $db->prepare($deleteTopicSql);
    $deleteTopicStmt->bindValue(":topic_id", $topicId);

    
    // TODO: Prepare, bind, and execute
    $success = $deleteTopicStmt->execute();
    
    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error with 500 status
    if ($success && $deleteTopicStmt->rowCount() > 0) {
        echo json_encode([
            "success" => true,
            "message" => "Topic deleted successfully."
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to delete topic."
        ]);
    }
}


// ============================================================================
// REPLIES FUNCTIONS
// ============================================================================

/**
 * Function: Get all replies for a specific topic
 * Method: GET
 * 
 * Query Parameters:
 *   - topic_id: The topic's unique identifier
 */
function getRepliesByTopicId($db, $topicId) {
    // TODO: Validate that topicId is provided
    // If not provided, return error with 400 status
     if (empty($topicId)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "topic_id is required."
        ]);
        return;
    }
    
    // TODO: Prepare SQL query to select all replies for the topic
    // Select reply_id, topic_id, text, author, and created_at (formatted as date)
    // Order by created_at ASC (oldest first)
    $sql = "SELECT reply_id, topic_id, text, author, DATE(created_at) AS created_at
            FROM replies
            WHERE topic_id = :topic_id
            ORDER BY created_at ASC";

    
    // TODO: Prepare and bind the topic_id parameter
    $stmt = $db->prepare($sql);
    $stmt->bindValue(":topic_id", $topicId);

    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Fetch all results as an associative array
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // TODO: Return JSON response
    // Even if no replies found, return empty array (not an error)
     echo json_encode([
        "success" => true,
        "data" => $replies
    ]);
}


/**
 * Function: Create a new reply
 * Method: POST
 * 
 * Required JSON Body:
 *   - reply_id: Unique identifier (e.g., "reply_1234567890")
 *   - topic_id: The parent topic's identifier
 *   - text: Reply message text
 *   - author: Author's name
 */
function createReply($db, $data) {
    // TODO: Validate required fields
    // Check if reply_id, topic_id, text, and author are provided
    // If any field is missing, return error with 400 status
    if (
        !isset($data['reply_id']) ||
        !isset($data['topic_id']) ||
        !isset($data['text']) ||
        !isset($data['author'])
    ) {
        http_response_code(400);
        return ["error" => "Missing required fields (reply_id, topic_id, text, author)."];
    }
    
    // TODO: Sanitize input data
    // Trim whitespace from all fields
    $replyId = trim($data['reply_id']);
    $topicId = trim($data['topic_id']);
    $text    = trim($data['text']);
    $author  = trim($data['author']);
    
    // TODO: Verify that the parent topic exists
    // Prepare and execute SELECT query on topics table
    // If topic doesn't exist, return error with 404 status (can't reply to non-existent topic)
    $checkTopic = $db->prepare("SELECT topic_id FROM topics WHERE topic_id = ?");
    $checkTopic->execute([$topicId]);

    if ($checkTopic->rowCount() === 0) {
        http_response_code(404);
        return ["error" => "Parent topic not found. Cannot add reply to non-existent topic."];
    }
    
    // TODO: Check if reply_id already exists
    // Prepare and execute SELECT query to check for duplicate
    // If duplicate found, return error with 409 status
     $checkDuplicate = $db->prepare("SELECT reply_id FROM replies WHERE reply_id = ?");
    $checkDuplicate->execute([$replyId]);

    if ($checkDuplicate->rowCount() > 0) {
        http_response_code(409);
        return ["error" => "Reply ID already exists."];
    }
    
    // TODO: Prepare INSERT query
    // Insert reply_id, topic_id, text, and author
    $sql = "INSERT INTO replies (reply_id, topic_id, text, author) 
            VALUES (:reply_id, :topic_id, :text, :author)";

    $stmt = $db->prepare($sql);
    
    // TODO: Prepare statement and bind parameters
    $stmt->bindParam(':reply_id', $replyId);
    $stmt->bindParam(':topic_id', $topicId);
    $stmt->bindParam(':text', $text);
    $stmt->bindParam(':author', $author);
    
    // TODO: Execute the query
    
    // TODO: Check if insert was successful
    // If yes, return success response with 201 status
    // Include the reply_id in the response
    // If no, return error with 500 status
    if ($stmt->execute()) {
        http_response_code(201);
        return [
            "message" => "Reply created successfully",
            "reply_id" => $replyId
        ];
    } else {
        http_response_code(500);
        return ["error" => "Failed to create reply"];
    }
}


/**
 * Function: Delete a reply
 * Method: DELETE
 * 
 * Query Parameters:
 *   - id: The reply's unique identifier
 */
function deleteReply($db, $replyId) {
    // TODO: Validate that replyId is provided
    // If not, return error with 400 status
    if (empty($replyId)) {
    http_response_code(400);
    echo json_encode(["error" => "replyId is required"]);
    return;
}

    // TODO: Check if reply exists
    // Prepare and execute SELECT query
    // If not found, return error with 404 status
    $checkSql = "SELECT reply_id FROM replies WHERE reply_id = :reply_id";
$checkStmt = $db->prepare($checkSql);
$checkStmt->bindParam(':reply_id', $replyId);
$checkStmt->execute();

if ($checkStmt->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(["error" => "Reply not found"]);
    return;
}

    
    // TODO: Prepare DELETE query
    $sql = "DELETE FROM replies WHERE reply_id = :reply_id";

    
    // TODO: Prepare, bind, and execute
    $stmt = $db->prepare($sql);
$stmt->bindParam(':reply_id', $replyId);
$stmt->execute();

    
    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error with 500 status
    if ($stmt->rowCount() > 0) {
    echo json_encode(["message" => "Reply deleted successfully"]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to delete reply"]);
}

}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Route the request based on resource and HTTP method
    // TODO: For GET requests, check for 'id' parameter in $_GET
    // TODO: For DELETE requests, get id from query parameter or request body
    // TODO: For unsupported methods, return 405 Method Not Allowed
    // TODO: For invalid resources, return 400 Bad Request


    try {
    // TODO: Route the request based on resource and HTTP method
    if ($resource === "topics") {

        if ($method === "GET") {
            // TODO: For GET requests, check for 'id' parameter in $_GET
            if (isset($_GET['id'])) {
                getTopicById($db, $_GET['id']);
            } else {
                getAllTopics($db);
            }
            exit;
        }

        if ($method === "POST") {
            createTopic($db, $data);
            exit;
        }

        if ($method === "PUT") {
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(["error" => "id is required for update"]);
                exit;
            }
            $data['topic_id'] = $_GET['id'];
            updateTopic($db, $data);

        }

        if ($method === "DELETE") {
            // TODO: For DELETE requests, get id from query parameter or request body
            $id = $_GET['id'] ?? ($data['id'] ?? null);

            if (!$id) {
                http_response_code(400);
                echo json_encode(["error" => "id is required for delete"]);
                exit;
            }

            deleteTopic($db, $id);
            exit;
        }

        // TODO: For unsupported methods, return 405 Method Not Allowed
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        exit;
    }


    /* Replies Resource */
    if ($resource === "replies") {

        if ($method === "GET") {
            if (!isset($_GET['topic_id'])) {
                http_response_code(400);
                echo json_encode(["error" => "topic_id is required"]);
                exit;
            }
            getRepliesByTopicId($db, $_GET['topic_id']);
            exit;
        }

        if ($method === "POST") {
            createReply($db, $data);
            exit;
        }

        if ($method === "DELETE") {
            $id = $_GET['id'] ?? ($data['id'] ?? null);

            if (!$id) {
                http_response_code(400);
                echo json_encode(["error" => "Reply ID is required for delete"]);
                exit;
            }

            deleteReply($db, $id);
            exit;
        }

        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        exit;
    }


    // TODO: For invalid resources, return 400 Bad Request
    http_response_code(400);
    echo json_encode(["error" => "Invalid resource"]);

} 

    
} catch (PDOException $e) {
    // TODO: Handle database errors
    // DO NOT expose the actual error message to the client (security risk)
    // Log the error for debugging (optional)
    // Return generic error response with 500 status
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "A database error occurred. Please try again later."
    ]);
    
} catch (Exception $e) {
    // TODO: Handle general errors
    // Log the error for debugging
    // Return error response with 500 status
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "An unexpected error occurred. Please try again later."
    ]);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response and exit
 * 
 * @param mixed $data - Data to send (will be JSON encoded)
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    http_response_code($statusCode);
    // TODO: Echo JSON encoded data
    // Make sure to handle JSON encoding errors
    $json = json_encode($data);
    if ($json === false) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "error" => "JSON encoding failed."
        ]);
        exit();
    }

    echo $json;
    // TODO: Exit to prevent further execution
    exit();
}


/**
 * Helper function to sanitize string input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Check if data is a string
    // If not, return as is or convert to string
    if (!is_string($data)) {
        return $data; 
    }
    
    // TODO: Trim whitespace from both ends
    $data = trim($data);
    
    // TODO: Remove HTML and PHP tags
     $data = strip_tags($data);
    
    // TODO: Convert special characters to HTML entities (prevents XSS)
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    // TODO: Return sanitized data
    return $data;
}


/**
 * Helper function to validate resource name
 * 
 * @param string $resource - Resource name to validate
 * @return bool - True if valid, false otherwise
 */
function isValidResource($resource) {
    // TODO: Define allowed resources
    $allowed = ["topics", "replies"];
    // TODO: Check if resource is in the allowed list
    return in_array($resource, $allowed, true);
}

?>
