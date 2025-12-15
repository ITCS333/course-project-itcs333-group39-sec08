<?php
// ---------------- HEADERS ----------------
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ---------------- DB ----------------
require_once __DIR__ . '/../../../../includes/db.php';

try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// ---------------- HELPERS ----------------
function clean($v) {
    return htmlspecialchars(strip_tags(trim((string)$v)), ENT_QUOTES, 'UTF-8');
}

// ---------------- REQUEST ----------------
$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);
$id = $_GET['id'] ?? null;

// 🔥 METHOD OVERRIDE (PUT via POST — REQUIRED for Replit)
if ($method === 'POST' && isset($data['_method'])) {
    $method = strtoupper($data['_method']);
}

try {

    // ========== GET ==========
    if ($method === 'GET') {

        // Single topic
        if ($id) {
            $stmt = $db->prepare(
                "SELECT id, subject, message, author,
                 DATE_FORMAT(created_at, '%Y-%m-%d') AS date
                 FROM topics
                 WHERE id = ?"
            );
            $stmt->execute([$id]);
            $topic = $stmt->fetch();

            if ($topic) {
                echo json_encode($topic);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Topic not found']);
            }
            exit;
        }

        // All topics
        $stmt = $db->query(
            "SELECT id, subject, message, author,
             DATE_FORMAT(created_at, '%Y-%m-%d') AS date
             FROM topics
             ORDER BY created_at DESC"
        );

        echo json_encode($stmt->fetchAll());
        exit;
    }

    // ========== POST (CREATE) ==========
    if ($method === 'POST') {

        if (empty($data['subject']) || empty($data['message']) || empty($data['author'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing fields']);
            exit;
        }

        $stmt = $db->prepare(
            "INSERT INTO topics (subject, message, author)
             VALUES (?, ?, ?)"
        );

        $stmt->execute([
            clean($data['subject']),
            clean($data['message']),
            clean($data['author'])
        ]);

        http_response_code(201);
        echo json_encode(['id' => $db->lastInsertId()]);
        exit;
    }

    // ========== PUT (UPDATE) ==========
    if ($method === 'PUT') {

        if (
            empty($data['id']) ||
            empty($data['subject']) ||
            empty($data['message'])
        ) {
            http_response_code(400);
            echo json_encode(['error' => 'ID, subject and message required']);
            exit;
        }

        $stmt = $db->prepare(
            "UPDATE topics
             SET subject = ?, message = ?
             WHERE id = ?"
        );

        $stmt->execute([
            clean($data['subject']),
            clean($data['message']),
            $data['id']
        ]);

        echo json_encode(['success' => true]);
        exit;
    }

    // ========== DELETE ==========
    if ($method === 'DELETE') {

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID required']);
            exit;
        }

        $stmt = $db->prepare("DELETE FROM topics WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true]);
        exit;
    }

    // ========== FALLBACK ==========
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);

} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
