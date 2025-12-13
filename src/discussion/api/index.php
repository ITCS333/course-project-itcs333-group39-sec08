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

// Set headers for JSON response and CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include the database connection
require_once '../../includes/db.php';

// Get the PDO database connection
$database = new Database();
$db = $database->getConnection();

// Get the HTTP request method
$method = $_SERVER['REQUEST_METHOD'];

// Get the request body for POST and PUT requests
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Parse query parameters
$resource = $_GET['resource'] ?? '';
$id = $_GET['id'] ?? '';
$topicId = $_GET['topic_id'] ?? '';


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
    $sql = "SELECT topic_id, subject, message, author, DATE_FORMAT(created_at, '%Y-%m-%d') as date FROM topics";
    $params = [];
    
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $sql .= " WHERE subject LIKE :search OR message LIKE :search OR author LIKE :search";
        $params[':search'] = $search;
    }
    
    $sort = $_GET['sort'] ?? 'created_at';
    $order = $_GET['order'] ?? 'desc';
    
    $allowedSort = ['subject', 'author', 'created_at'];
    $allowedOrder = ['asc', 'desc'];
    
    if (!in_array($sort, $allowedSort)) $sort = 'created_at';
    if (!in_array(strtolower($order), $allowedOrder)) $order = 'desc';
    
    $sql .= " ORDER BY $sort $order";
    
    $stmt = $db->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendResponse(['success' => true, 'data' => $topics]);
}


/**
 * Function: Get a single topic by topic_id
 * Method: GET
 * 
 * Query Parameters:
 *   - id: The topic's unique identifier
 */
function getTopicById($db, $topicId) {
    if (empty($topicId)) {
        sendResponse(['success' => false, 'message' => 'Topic ID is required'], 400);
    }
    
    $sql = "SELECT topic_id, subject, message, author, DATE_FORMAT(created_at, '%Y-%m-%d') as date FROM topics WHERE topic_id = :topic_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':topic_id', $topicId);
    $stmt->execute();
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($topic) {
        sendResponse(['success' => true, 'data' => $topic]);
    } else {
        sendResponse(['success' => false, 'message' => 'Topic not found'], 404);
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
    if (empty($data['topic_id']) || empty($data['subject']) || empty($data['message']) || empty($data['author'])) {
        sendResponse(['success' => false, 'message' => 'All fields are required'], 400);
    }
    
    $topicId = sanitizeInput($data['topic_id']);
    $subject = sanitizeInput($data['subject']);
    $message = sanitizeInput($data['message']);
    $author = sanitizeInput($data['author']);
    
    $checkSql = "SELECT COUNT(*) FROM topics WHERE topic_id = :topic_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(':topic_id', $topicId);
    $checkStmt->execute();
    
    if ($checkStmt->fetchColumn() > 0) {
        sendResponse(['success' => false, 'message' => 'Topic ID already exists'], 409);
    }
    
    $sql = "INSERT INTO topics (topic_id, subject, message, author) VALUES (:topic_id, :subject, :message, :author)";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':topic_id', $topicId);
    $stmt->bindParam(':subject', $subject);
    $stmt->bindParam(':message', $message);
    $stmt->bindParam(':author', $author);
    
    if ($stmt->execute()) {
        sendResponse(['success' => true, 'message' => 'Topic created successfully', 'topic_id' => $topicId], 201);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to create topic'], 500);
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
    if (empty($data['topic_id'])) {
        sendResponse(['success' => false, 'message' => 'Topic ID is required'], 400);
    }
    
    $topicId = $data['topic_id'];
    $checkSql = "SELECT COUNT(*) FROM topics WHERE topic_id = :topic_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(':topic_id', $topicId);
    $checkStmt->execute();
    
    if ($checkStmt->fetchColumn() == 0) {
        sendResponse(['success' => false, 'message' => 'Topic not found'], 404);
    }
    
    $updateFields = [];
    $params = [];
    
    if (!empty($data['subject'])) {
        $updateFields[] = "subject = :subject";
        $params[':subject'] = sanitizeInput($data['subject']);
    }
    if (!empty($data['message'])) {
        $updateFields[] = "message = :message";
        $params[':message'] = sanitizeInput($data['message']);
    }
    if (!empty($data['author'])) {
        $updateFields[] = "author = :author";
        $params[':author'] = sanitizeInput($data['author']);
    }
    
    if (empty($updateFields)) {
        sendResponse(['success' => false, 'message' => 'No fields to update'], 400);
    }
    
    $sql = "UPDATE topics SET " . implode(', ', $updateFields) . " WHERE topic_id = :topic_id";
    $params[':topic_id'] = $topicId;
    
    $stmt = $db->prepare($sql);
    if ($stmt->execute($params)) {
        sendResponse(['success' => true, 'message' => 'Topic updated successfully', 'topic_id' => $topicId]);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to update topic'], 500);
    }
}


/**
 * Function: Delete a topic
 * Method: DELETE
 * 
 * Query Parameters:
 *   - id: The topic's unique identifier
 */
function deleteTopic($db, $topicId) {
    if (empty($topicId)) {
        sendResponse(['success' => false, 'message' => 'Topic ID is required'], 400);
    }
    
    $checkSql = "SELECT COUNT(*) FROM topics WHERE topic_id = :topic_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(':topic_id', $topicId);
    $checkStmt->execute();
    
    if ($checkStmt->fetchColumn() == 0) {
        sendResponse(['success' => false, 'message' => 'Topic not found'], 404);
    }
    
    $deleteRepliesSql = "DELETE FROM replies WHERE topic_id = :topic_id";
    $deleteRepliesStmt = $db->prepare($deleteRepliesSql);
    $deleteRepliesStmt->bindParam(':topic_id', $topicId);
    $deleteRepliesStmt->execute();
    
    $deleteTopicSql = "DELETE FROM topics WHERE topic_id = :topic_id";
    $deleteTopicStmt = $db->prepare($deleteTopicSql);
    $deleteTopicStmt->bindParam(':topic_id', $topicId);
    
    if ($deleteTopicStmt->execute()) {
        sendResponse(['success' => true, 'message' => 'Topic deleted successfully', 'topic_id' => $topicId]);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to delete topic'], 500);
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
    if (empty($topicId)) {
        sendResponse(['success' => false, 'message' => 'Topic ID is required'], 400);
    }
    
    $sql = "SELECT reply_id, topic_id, text, author, DATE_FORMAT(created_at, '%Y-%m-%d') as date FROM replies WHERE topic_id = :topic_id ORDER BY created_at ASC";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':topic_id', $topicId);
    $stmt->execute();
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendResponse(['success' => true, 'data' => $replies]);
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
    if (empty($data['reply_id']) || empty($data['topic_id']) || empty($data['text']) || empty($data['author'])) {
        sendResponse(['success' => false, 'message' => 'All fields are required'], 400);
    }
    
    $replyId = sanitizeInput($data['reply_id']);
    $topicId = sanitizeInput($data['topic_id']);
    $text = sanitizeInput($data['text']);
    $author = sanitizeInput($data['author']);
    
    $topicCheckSql = "SELECT COUNT(*) FROM topics WHERE topic_id = :topic_id";
    $topicCheckStmt = $db->prepare($topicCheckSql);
    $topicCheckStmt->bindParam(':topic_id', $topicId);
    $topicCheckStmt->execute();
    
    if ($topicCheckStmt->fetchColumn() == 0) {
        sendResponse(['success' => false, 'message' => 'Topic not found'], 404);
    }
    
    $replyCheckSql = "SELECT COUNT(*) FROM replies WHERE reply_id = :reply_id";
    $replyCheckStmt = $db->prepare($replyCheckSql);
    $replyCheckStmt->bindParam(':reply_id', $replyId);
    $replyCheckStmt->execute();
    
    if ($replyCheckStmt->fetchColumn() > 0) {
        sendResponse(['success' => false, 'message' => 'Reply ID already exists'], 409);
    }
    
    $sql = "INSERT INTO replies (reply_id, topic_id, text, author) VALUES (:reply_id, :topic_id, :text, :author)";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':reply_id', $replyId);
    $stmt->bindParam(':topic_id', $topicId);
    $stmt->bindParam(':text', $text);
    $stmt->bindParam(':author', $author);
    
    if ($stmt->execute()) {
        sendResponse(['success' => true, 'message' => 'Reply created successfully', 'reply_id' => $replyId], 201);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to create reply'], 500);
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
    if (empty($replyId)) {
        sendResponse(['success' => false, 'message' => 'Reply ID is required'], 400);
    }
    
    $checkSql = "SELECT COUNT(*) FROM replies WHERE reply_id = :reply_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(':reply_id', $replyId);
    $checkStmt->execute();
    
    if ($checkStmt->fetchColumn() == 0) {
        sendResponse(['success' => false, 'message' => 'Reply not found'], 404);
    }
    
    $deleteSql = "DELETE FROM replies WHERE reply_id = :reply_id";
    $deleteStmt = $db->prepare($deleteSql);
    $deleteStmt->bindParam(':reply_id', $replyId);
    
    if ($deleteStmt->execute()) {
        sendResponse(['success' => true, 'message' => 'Reply deleted successfully', 'reply_id' => $replyId]);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to delete reply'], 500);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // Validate resource parameter
    if (empty($resource) || !isValidResource($resource)) {
        sendResponse(['success' => false, 'message' => 'Invalid or missing resource parameter. Valid resources are: topics, replies'], 400);
    }
    
    // Route the request based on resource and HTTP method
    switch ($resource) {
        case 'topics':
            // For GET requests, check for 'id' parameter in $_GET
            if ($method === 'GET') {
                if (!empty($id)) {
                    getTopicById($db, $id);
                } else {
                    getAllTopics($db);
                }
            }
            // For POST requests
            elseif ($method === 'POST') {
                createTopic($db, $data ?? []);
            }
            // For PUT requests
            elseif ($method === 'PUT') {
                updateTopic($db, $data ?? []);
            }
            // For DELETE requests, get id from query parameter or request body
            elseif ($method === 'DELETE') {
                // Try to get id from query parameter first, then from request body
                $deleteId = $id;
                if (empty($deleteId) && isset($data['topic_id'])) {
                    $deleteId = $data['topic_id'];
                }
                deleteTopic($db, $deleteId);
            }
            // For unsupported methods, return 405 Method Not Allowed
            else {
                sendResponse(['success' => false, 'message' => 'Method not allowed for this resource'], 405);
            }
            break;
            
        case 'replies':
            // For GET requests with topic_id parameter
            if ($method === 'GET') {
                getRepliesByTopicId($db, $topicId);
            }
            // For POST requests
            elseif ($method === 'POST') {
                createReply($db, $data ?? []);
            }
            // For DELETE requests, get id from query parameter or request body
            elseif ($method === 'DELETE') {
                // Try to get id from query parameter first, then from request body
                $deleteId = $id;
                if (empty($deleteId) && isset($data['reply_id'])) {
                    $deleteId = $data['reply_id'];
                }
                deleteReply($db, $deleteId);
            }
            // For unsupported methods, return 405 Method Not Allowed
            else {
                sendResponse(['success' => false, 'message' => 'Method not allowed for this resource'], 405);
            }
            break;
            
        default:
            // For invalid resources (should be caught earlier, but keeping for safety)
            sendResponse(['success' => false, 'message' => 'Invalid resource'], 400);
    }
    
} catch (PDOException $e) {
    // Handle database errors
    // DO NOT expose the actual error message to the client (security risk)
    error_log('Database error in discussion API: ' . $e->getMessage()); // Log the error for debugging
    sendResponse(['success' => false, 'message' => 'Database error occurred'], 500);
    
} catch (Exception $e) {
    // Handle general errors
    error_log('General error in discussion API: ' . $e->getMessage()); // Log the error for debugging
    sendResponse(['success' => false, 'message' => 'An unexpected error occurred'], 500);
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
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}


/**
 * Helper function to sanitize string input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    if (!is_string($data)) {
        $data = (string)$data;
    }
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}


/**
 * Helper function to validate resource name
 * 
 * @param string $resource - Resource name to validate
 * @return bool - True if valid, false otherwise
 */
function isValidResource($resource) {
    $allowedResources = ['topics', 'replies'];
    return in_array($resource, $allowedResources);
}

?>
