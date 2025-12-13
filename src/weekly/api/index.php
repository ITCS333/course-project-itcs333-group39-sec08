<!--
  Requirement: Create the Course Homepage

  Instructions:
  This page will serve as the main entry point to the course website.
  It should contain a simple navigation menu with links to all the key pages.
-->
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- TODO: Add the 'meta' tag for character encoding (UTF-8). -->
    <meta charset="UTF-8">
    <!-- TODO: Add the responsive 'viewport' meta tag. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- TODO: Add a title, e.g., "Web Dev Course Homepage". -->
     <title>Web Dev Course Homepage</title>
    <!-- TODO: Link to your CSS framework or stylesheet. -->
     <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@latest/css/pico.min.css">
</head>
<body>

    <!-- TODO: Create a 'header' with a main heading ('h1') like "Welcome to the Web Development Course". -->
     <header>
        <h1>Welcome to the Web Development Course</h1>
        <p>This portal gives access to all course resources, weeks, assignments, and discussions.</p>
    </header>
    <!-- TODO: Create the 'main' content area. -->
     <main>
        <!-- TODO: Create a 'nav' element to hold the site navigation. -->
         <nav>
            <!-- TODO: Add a sub-heading ('h2') "Site Navigation". -->
             <h2>Site Navigation</h2>
            <!-- TODO: Create an unordered list ('ul') to hold the links. -->
             <ul>
                <!-- Section: General -->
                <!-- TODO: Add a list item ('li') with a link ('a') to the Login page.
                     - Text: "Login" -->
                    <li><a href="src/auth/login.html">Login</a></li>
                <!-- Section: Admin Pages -->
                <!-- TODO: Add a list item ('li') with a link ('a') to the Admin Portal.
                     - Text: "Admin Portal (Manage Students)" -->
                    <li><a href="src/admin/manage_users.html">Admin Portal (Manage Students)</a></li>
                <!-- TODO: Add a list item ('li') with a link ('a') to the Admin Resources page.
                     - Text: "Admin: Manage Resources" -->
                    <li><a href="src/resources/admin.html">Admin: Manage Resources</a></li>
                <!-- TODO: Add a list item ('li') with a link ('a') to the Admin Weekly Breakdown page.
                     - Text: "Admin: Manage Weekly Breakdown" -->
                    <li><a href="src/weekly/admin.html">Admin: Manage Weekly Breakdown</a></li>        
                <!-- TODO: Add a list item ('li') with a link ('a') to the Admin Assignments page.
                     - Text: "Admin: Manage Assignments" -->
                     <li><a href="src/assignments/admin.html">Admin: Manage Assignments</a></li>
                <!-- Section: Student Pages -->
                <!-- TODO: Add a list item ('li') with a link ('a') to the Student Resources page.
                     - Text: "View Course Resources" -->
                    <li><a href="src/resources/list.html">View Course Resources</a></li>
                <!-- TODO: Add a list item ('li') with a link ('a') to the Student Weekly Breakdown page.
                     - Text: "View Weekly Breakdown" -->
                    <li><a href="src/weekly/list.html">View Weekly Breakdown</a></li>
                <!-- TODO: Add a list item ('li') with a link ('a') to the Student Assignments page.
                     - Text: "View Assignments" -->
                    <li><a href="src/assignments/list.html">View Assignments</a></li>
                <!-- TODO: Add a list item ('li') with a link ('a') to the Discussion Board.
                     - Text: "General Discussion Board" -->
                    <li><a href="src/discussion/board.html">General Discussion Board</a></li>
            <!-- End of the unordered list. -->
             </ul>
        <!-- End of the 'nav' element. -->
         </nav>
    <!-- End of 'main'. -->
     </main>
</body>
</html>

<?php



/**
 * Weekly Course Breakdown API
 * 
 * This is a RESTful API that handles all CRUD operations for weekly course content
 * and discussion comments. It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: weeks
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50), UNIQUE) - Unique identifier (e.g., "week_1")
 *   - title (VARCHAR(200))
 *   - start_date (DATE)
 *   - description (TEXT)
 *   - links (TEXT) - JSON encoded array of links
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50)) - Foreign key reference to weeks.week_id
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported: 
 *   - GET: Retrieve week(s) or comment(s)
 *   - POST: Create a new week or comment
 *   - PUT: Update an existing week
 *   - DELETE: Delete a week or comment
 * 
 * Response Format: JSON
 */

// ============================================================================
// SETUP AND CONFIGURATION
// ============================================================================

// TODO: Set headers for JSON response and CORS (implemented below)
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');


// TODO: Handle preflight OPTIONS request (implemented below)
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


// TODO: Include the database connection class (implemented below)
// Assume the Database class has a method getConnection() that returns a PDO instance
// Example: require_once '../config/Database.php';
// Use the simple includes/db.php which defines a $pdo PDO instance
require_once __DIR__ . '/../includes/db.php';


// TODO: Get the PDO database connection (implemented below)
// Example: $database = new Database();
//          $db = $database->getConnection();
// $pdo is provided by includes/db.php
$db = isset($pdo) ? $pdo : null;
if (!$db) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection not available']);
    exit;
}


// TODO: Get the HTTP request method (implemented below)
// Use $_SERVER['REQUEST_METHOD']
$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';


// TODO: Get the request body for POST and PUT requests (implemented below)
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()
$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true);
if (!is_array($inputData)) {
    $inputData = [];
}


// TODO: Parse query parameters (implemented below)
// Get the 'resource' parameter to determine if request is for weeks or comments
// Example: ?resource=weeks or ?resource=comments
$resource = isset($_GET['resource']) ? $_GET['resource'] : 'weeks';
$queryId = isset($_GET['id']) ? $_GET['id'] : null;



// ============================================================================
// WEEKS CRUD OPERATIONS
// ============================================================================


// #1 function 



/**
 * Function: Get all weeks or search for specific weeks
 * Method: GET
 * Resource: weeks
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, start_date)
 *   - order: Optional sort order (asc or desc, default: asc)
 */
function getAllWeeks($db) {
    // TODO: Initialize variables for search, sort, and order from query parameters (implemented below)
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'start_date';
    $order = isset($_GET['order']) ? strtolower(trim($_GET['order'])) : 'asc';

    // Validate sort and order
    $allowedSort = ['title', 'start_date', 'created_at'];
    if (!isValidSortField($sort, $allowedSort)) {
        $sort = 'start_date';
    }
    if ($order !== 'asc' && $order !== 'desc') {
        $order = 'asc';
    }

    $sql = "SELECT id, title, start_date, description, links, created_at FROM weeks";
    $params = [];
    if ($search !== '') {
        $sql .= " WHERE title LIKE ? OR description LIKE ?";
        $like = "%{$search}%";
        $params[] = $like;
        $params[] = $like;
    }

    $sql .= " ORDER BY {$sort} {$order}";

    $stmt = $db->prepare($sql);
    if (!empty($params)) {
        foreach ($params as $i => $p) {
            $stmt->bindValue($i + 1, $p);
        }
    }
    $stmt->execute();
    $weeks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decode links JSON
    foreach ($weeks as &$w) {
        $w['links'] = $w['links'] ? json_decode($w['links'], true) : [];
    }

    sendResponse(['success' => true, 'data' => $weeks]);
}





// #2 function

/**
 * Function: Get a single week by week_id
 * Method: GET
 * Resource: weeks
 * 
 * Query Parameters:
 *   - week_id: The unique week identifier (e.g., "week_1")
 */
function getWeekById($db, $weekId) {
    // TODO: Validate that week_id is provided (implemented below)
    // If not, return error response with 400 status
    if (!$weekId) {
        sendError('week_id is required', 400);
    }

    $sql = "SELECT id, title, start_date, description, links, created_at FROM weeks WHERE id = ? LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(1, $weekId);
    $stmt->execute();
    $week = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($week) {
        $week['links'] = $week['links'] ? json_decode($week['links'], true) : [];
        sendResponse(['success' => true, 'data' => $week]);
    } else {
        sendError('Week not found', 404);
    }
}

  


// #3 function
/**
 * Function: Create a new week
 * Method: POST
 * Resource: weeks
 * 
 * Required JSON Body:
 *   - week_id: Unique week identifier (e.g., "week_1")
 *   - title: Week title (e.g., "Week 1: Introduction to HTML")
 *   - start_date: Start date in YYYY-MM-DD format
 *   - description: Week description
 *   - links: Array of resource links (will be JSON encoded)
 */
function createWeek($db, $data) {
    // TODO: Validate required fields (implemented below)
    // Check if week_id, title, start_date, and description are provided
    // If any field is missing, return error response with 400 status
    $required = ['week_id', 'title', 'start_date', 'description'];
    foreach ($required as $r) {
        if (!isset($data[$r]) || trim($data[$r]) === '') {
            sendError("$r is required", 400);
        }
    }

    $week_id = sanitizeInput($data['id']);
    $title = sanitizeInput($data['title']);
    $start_date = sanitizeInput($data['start_date']);
    $description = sanitizeInput($data['description']);

    if (!validateDate($start_date)) {
        sendError('start_date must be in YYYY-MM-DD format', 400);
    }

    // Check duplicate week_id
    $stmt = $db->prepare('SELECT COUNT(*) FROM weeks WHERE week_id = ?');
    $stmt->bindValue(1, $week_id);
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
        sendError('week_id already exists', 409);
    }

    $links = [];
    if (isset($data['links']) && is_array($data['links'])) {
        $links = $data['links'];
    }
    $linksJson = json_encode(array_values($links));

    $insert = $db->prepare('INSERT INTO weeks (week_id, title, start_date, description, links, created_at) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)');
    $insert->bindValue(1, $week_id);
    $insert->bindValue(2, $title);
    $insert->bindValue(3, $start_date);
    $insert->bindValue(4, $description);
    $insert->bindValue(5, $linksJson);

    if ($insert->execute()) {
        $newWeek = [
            'week_id' => $week_id,
            'title' => $title,
            'start_date' => $start_date,
            'description' => $description,
            'links' => $links,
            'created_at' => date('Y-m-d H:i:s')
        ];
        sendResponse(['success' => true, 'data' => $newWeek], 201);
    } else {
        sendError('Failed to create week', 500);
    }
}





// #4 function
/**
 * Function: Update an existing week
 * Method: PUT
 * Resource: weeks
 * 
 * Required JSON Body:
 *   - week_id: The week identifier (to identify which week to update)
 *   - title: Updated week title (optional)
 *   - start_date: Updated start date (optional)
 *   - description: Updated description (optional)
 *   - links: Updated array of links (optional)
 */
function updateWeek($db, $data) {
    // TODO: Validate that week_id is provided (implemented below)
    // If not, return error response with 400 status
    if (!isset($data['id']) || trim($data['id']) === '') {
        sendError('id is required for update', 400);
    }
    $week_id = sanitizeInput($data['id']);

    // Check exists
    $check = $db->prepare('SELECT * FROM weeks WHERE id = ? LIMIT 1');
    $check->bindValue(1, $week_id);
    $check->execute();
    $existing = $check->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        sendError('Week not found', 404);
    }

    $set = [];
    $values = [];
    if (isset($data['title'])) {
        $set[] = 'title = ?';
        $values[] = sanitizeInput($data['title']);
    }
    if (isset($data['start_date'])) {
        $sd = sanitizeInput($data['start_date']);
        if (!validateDate($sd)) {
            sendError('start_date must be in YYYY-MM-DD format', 400);
        }
        $set[] = 'start_date = ?';
        $values[] = $sd;
    }
    if (isset($data['description'])) {
        $set[] = 'description = ?';
        $values[] = sanitizeInput($data['description']);
    }
    if (isset($data['links']) && is_array($data['links'])) {
        $set[] = 'links = ?';
        $values[] = json_encode(array_values($data['links']));
    }

    if (empty($set)) {
        sendError('No fields provided to update', 400);
    }

    $set[] = 'updated_at = CURRENT_TIMESTAMP';
    $sql = 'UPDATE weeks SET ' . implode(', ', $set) . ' WHERE id = ?';
    $stmt = $db->prepare($sql);
    $i = 1;
    foreach ($values as $v) {
        $stmt->bindValue($i, $v);
        $i++;
    }
    $stmt->bindValue($i, $week_id);

    if ($stmt->execute()) {
        // Return the updated week
        $get = $db->prepare('SELECT week_id, title, start_date, description, links, created_at, updated_at FROM weeks WHERE week_id = ? LIMIT 1');
        $get->bindValue(1, $week_id);
        $get->execute();
        $week = $get->fetch(PDO::FETCH_ASSOC);
        if ($week) {
            $week['links'] = $week['links'] ? json_decode($week['links'], true) : [];
        }
        sendResponse(['success' => true, 'data' => $week]);
    } else {
        sendError('Failed to update week', 500);
    }
}




// #5 function

/**
 * Function: Delete a week
 * Method: DELETE
 * Resource: weeks
 * 
 * Query Parameters or JSON Body:
 *   - week_id: The week identifier
 */
function deleteWeek($db, $weekId) {
    // TODO: Validate that week_id is provided (implemented below)
    // If not, return error response with 400 status
    if (!$weekId) {
        sendError('week_id is required', 400);
    }

    $check = $db->prepare('SELECT * FROM weeks WHERE id = ? LIMIT 1');
    $check->bindValue(1, $weekId);
    $check->execute();
    if (!$check->fetch()) {
        sendError('Week not found', 404);
    }

    // Delete comments first
    $delComments = $db->prepare('DELETE FROM comments_week WHERE week_id = ?');
    $delComments->bindValue(1, $weekId);
    $delComments->execute();

    // Delete the week
    $delWeek = $db->prepare('DELETE FROM weeks WHERE id = ?');
    $delWeek->bindValue(1, $weekId);
    if ($delWeek->execute()) {
        sendResponse(['success' => true, 'message' => 'Week and associated comments deleted']);
    } else {
        sendError('Failed to delete week', 500);
    }
}


// ============================================================================
// COMMENTS CRUD OPERATIONS
// ============================================================================




// #6 function

/**
 * Function: Get all comments for a specific week
 * Method: GET
 * Resource: comments
 * 
 * Query Parameters:
 *   - week_id: The week identifier to get comments for
 */
function getCommentsByWeek($db, $weekId) {
    // TODO: Validate that week_id is provided (implemented below)
    // If not, return error response with 400 status
    if (!$weekId) {
        sendError('week_id is required', 400);
    }
    $stmt = $db->prepare('SELECT id, week_id, author, text, created_at FROM comments_week WHERE week_id = ? ORDER BY created_at ASC');
    $stmt->bindValue(1, $weekId);
    $stmt->execute();
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success' => true, 'data' => $comments]);
}


// #7 function

/**
 * Function: Create a new comment
 * Method: POST
 * Resource: comments
 * 
 * Required JSON Body:
 *   - week_id: The week identifier this comment belongs to
 *   - author: Comment author name
 *   - text: Comment text content
 */
function createComment($db, $data) {
    // TODO: Validate required fields (implemented below)
    // Check if week_id, author, and text are provided
    // If any field is missing, return error response with 400 status
    $required = ['week_id', 'author', 'text'];
    foreach ($required as $r) {
        if (!isset($data[$r]) || trim($data[$r]) === '') {
            sendError("$r is required", 400);
        }
    }

    $week_id = sanitizeInput($data['id']);
    $author = sanitizeInput($data['author']);
    $text = sanitizeInput($data['text']);
    if ($text === '') {
        sendError('text cannot be empty', 400);
    }

    // Check week exists
    $check = $db->prepare('SELECT id FROM weeks WHERE id = ? LIMIT 1');
    $check->bindValue(1, $week_id);
    $check->execute();
    if (!$check->fetch()) {
        sendError('Week not found', 404);
    }

    $ins = $db->prepare('INSERT INTO comments_week (week_id, author, text, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)');
    $ins->bindValue(1, $week_id);
    $ins->bindValue(2, $author);
    $ins->bindValue(3, $text);

    if ($ins->execute()) {
        $id = $db->lastInsertId();
        $new = [
            'id' => (int)$id,
            'week_id' => $week_id,
            'author' => $author,
            'text' => $text,
            'created_at' => date('Y-m-d H:i:s')
        ];
        sendResponse(['success' => true, 'data' => $new], 201);
    } else {
        sendError('Failed to create comment', 500);
    }
}


// #8 function

/**
 * Function: Delete a comment
 * Method: DELETE
 * Resource: comments
 * 
 * Query Parameters or JSON Body:
 *   - id: The comment ID to delete
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that id is provided (implemented below)
    // If not, return error response with 400 status
    if (!$commentId) {
        sendError('comment id is required', 400);
    }
    $check = $db->prepare('SELECT * FROM comments_week WHERE id = ? LIMIT 1');
    $check->bindValue(1, $commentId);
    $check->execute();
    if (!$check->fetch()) {
        sendError('Comment not found', 404);
    }

    $del = $db->prepare('DELETE FROM comments_week WHERE id = ?');
    $del->bindValue(1, $commentId);
    if ($del->execute()) {
        sendResponse(['success' => true, 'message' => 'Comment deleted']);
    } else {
        sendError('Failed to delete comment', 500);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Determine the resource type from query parameters (implemented above)
    // Get 'resource' parameter (?resource=weeks or ?resource=comments)
    // If not provided, default to 'weeks'
    // Route based on resource type and HTTP method
    // ========== WEEKS ROUTES ==========
    if ($resource === 'weeks') {
        if ($method === 'GET') {
            // If week_id provided in query, return single week
            if (!empty($queryId)) {
                getWeekById($db, $queryId);
            } else {
                getAllWeeks($db);
            }
        } elseif ($method === 'POST') {
            createWeek($db, $inputData);
        } elseif ($method === 'PUT') {
            // PUT body should contain week_id and fields to update
            updateWeek($db, $inputData);
        } elseif ($method === 'DELETE') {
            // week_id can be provided via query or body
            $wid = $queryId ?: (isset($inputData['id']) ? $inputData['id'] : null);
            deleteWeek($db, $wid);
        } else {
            sendError('Method not allowed for weeks', 405);
        }
    }
    // ========== COMMENTS ROUTES ==========
    elseif ($resource === 'comments') {
        if ($method === 'GET') {
            $wid = isset($_GET['week_id']) ? $_GET['week_id'] : $queryId;
            getCommentsByWeek($db, $wid);
        } elseif ($method === 'POST') {
            createComment($db, $inputData);
        } elseif ($method === 'DELETE') {
            $cid = $queryId ?: (isset($inputData['id']) ? $inputData['id'] : null);
            deleteComment($db, $cid);
        } else {
            sendError('Method not allowed for comments', 405);
        }
    }
    // ========== INVALID RESOURCE ==========
    else {
        sendError("Invalid resource. Use 'weeks' or 'comments'", 400);
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors (implemented below)
    // Log the error message (optional, for debugging)
    // error_log($e->getMessage());
    
    // TODO: Return generic error response with 500 status (implemented below)
    // Do NOT expose database error details to the client
    // Return message: "Database error occurred"
    error_log('[weekly/api] PDOException: ' . $e->getMessage());
    sendError('Database error occurred', 500);
    
} catch (Exception $e) {
    // TODO: Handle general errors (implemented below)
    // Log the error message (optional)
    // Return error response with 500 status
    error_log('[weekly/api] Exception: ' . $e->getMessage());
    sendError('An internal error occurred', 500);
}





// #9 funaction

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param mixed $data - Data to send (will be JSON encoded)
 * @param int $statusCode - HTTP status code (default: 200)
 */




// function 1

function sendResponse($data, $statusCode = 200) {
    // Set HTTP response code
    http_response_code($statusCode);

    // Ensure Content-Type header for JSON (in case not already set)
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }

    // Encode data to JSON with safe options
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        // json_encode failed; return a safe error message
        http_response_code(500);
        $err = ['success' => false, 'error' => 'Failed to encode response as JSON'];
        echo json_encode($err, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    echo $json;
    exit;
}


/**
 * Helper function to send error response
 * 
 * @param string $message - Error message
 * @param int $statusCode - HTTP status code
 */


// function 2

function sendError($message, $statusCode = 400) {
    // Create standardized error response and send
    $err = ['success' => false, 'error' => $message];
    sendResponse($err, $statusCode);
}


/**
 * Helper function to validate date format (YYYY-MM-DD)
 * 
 * @param string $date - Date string to validate
 * @return bool - True if valid, false otherwise
 */

// function 3

function validateDate($date) {
    // Use DateTime::createFromFormat() to validate
    // Format: 'Y-m-d'
    if (!is_string($date)) {
        return false;
    }
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */

// function 4

function sanitizeInput($data) {
    // Support arrays by sanitizing recursively
    if (is_array($data)) {
        $out = [];
        foreach ($data as $k => $v) {
            $out[$k] = sanitizeInput($v);
        }
        return $out;
    }

    // Non-strings are returned as-is
    if (!is_string($data)) {
        return $data;
    }

    // Trim, strip tags and escape special characters
    $d = trim($data);
    $d = strip_tags($d);
    $d = htmlspecialchars($d, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return $d;
}


/**
 * Helper function to validate allowed sort fields
 * 
 * @param string $field - Field name to validate
 * @param array $allowedFields - Array of allowed field names
 * @return bool - True if valid, false otherwise
 */

// function 5
function isValidSortField($field, $allowedFields) {
    // Basic validation of inputs
    if (!is_string($field) || $field === '' || !is_array($allowedFields)) {
        return false;
    }

    // Exact-match check (strict) to avoid accidental SQL injection
    return in_array($field, $allowedFields, true);
}

?>
