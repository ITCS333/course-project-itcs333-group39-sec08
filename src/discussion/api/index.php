<?php
session_start();
$_SESSION['user_data'] = $_SESSION['user_data'] ?? [];

// HEADERS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// DB
require_once __DIR__ . '/../../../../includes/db.php';
$database = new Database();
$db = $database->getConnection();

// HELPERS
function clean($v) {
    return htmlspecialchars(strip_tags(trim((string)$v)), ENT_QUOTES, 'UTF-8');
}

// REQUEST
$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);
$id = $_GET['id'] ?? null;
$topicId = $_GET['topic_id'] ?? null;

// 🔥 METHOD OVERRIDE
if ($method === 'POST' && isset($data['_method'])) {
    $method = strtoupper($data['_method']);
}

// GET
if ($method === 'GET') {
    if (!$topicId) {
        echo json_encode([]);
        exit;
    }

    $stmt = $db->prepare(
        "SELECT id, text, author,
         DATE_FORMAT(created_at, '%Y-%m-%d') AS date
         FROM replies
         WHERE topic_id = ?
         ORDER BY created_at ASC"
    );
    $stmt->execute([$topicId]);
    echo json_encode($stmt->fetchAll());
    exit;
}

// POST (CREATE)
if ($method === 'POST') {
    if (empty($data['topic_id']) || empty($data['text']) || empty($data['author'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing fields']);
        exit;
    }

    $stmt = $db->prepare(
        "INSERT INTO replies (topic_id, text, author)
         VALUES (?, ?, ?)"
    );
    $stmt->execute([
        $data['topic_id'],
        clean($data['text']),
        clean($data['author'])
    ]);

    echo json_encode(['id' => $db->lastInsertId()]);
    exit;
}

// PUT (UPDATE)
if ($method === 'PUT') {
    if (empty($data['id']) || empty($data['text'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID and text required']);
        exit;
    }

    $stmt = $db->prepare(
        "UPDATE replies SET text = ? WHERE id = ?"
    );
    $stmt->execute([
        clean($data['text']),
        $data['id']
    ]);

    echo json_encode(['success' => true]);
    exit;
}

// DELETE
if ($method === 'DELETE') {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID required']);
        exit;
    }

    $stmt = $db->prepare("DELETE FROM replies WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
