<?php
session_start();

// ================== DEBUG (REMOVE IN PRODUCTION) ==================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ================== HEADERS ==================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ================== DB CONNECTION ==================
require_once __DIR__ . '/../../../../includes/db.php';

try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// ================== HELPERS ==================
function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function clean($v) {
    return htmlspecialchars(strip_tags(trim((string)$v)), ENT_QUOTES, 'UTF-8');
}

// ================== REQUEST ==================
$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true) ?? [];
$id = $_GET['id'] ?? null;
$resource = $_GET['resource'] ?? 'weeks';

// =================================================
// ================== WEEKS ========================
// =================================================

if ($resource === 'weeks') {

    // ---------- GET ALL ----------
    if ($method === 'GET' && !$id) {
        $stmt = $db->query("
            SELECT id, title, start_date, description, links
            FROM weeks
            ORDER BY start_date ASC
        ");

        $weeks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($weeks as &$w) {
            $w['links'] = $w['links'] ? json_decode($w['links'], true) : [];
        }

        respond(['success' => true, 'data' => $weeks]);
    }

    // ---------- GET ONE ----------
    if ($method === 'GET' && $id) {
        $stmt = $db->prepare("
            SELECT id, title, start_date, description, links
            FROM weeks
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        $week = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$week) {
            respond(['success' => false, 'error' => 'Week not found'], 404);
        }

        $week['links'] = $week['links'] ? json_decode($week['links'], true) : [];
        respond(['success' => true, 'data' => $week]);
    }

    // ---------- CREATE ----------
    if ($method === 'POST') {
        if (empty($data['title']) || empty($data['start_date'])) {
            respond(['success' => false, 'error' => 'Title and start date required'], 400);
        }

        $stmt = $db->prepare("
            INSERT INTO weeks (title, start_date, description, links)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            clean($data['title']),
            clean($data['start_date']),
            clean($data['description'] ?? ''),
            json_encode($data['links'] ?? [])
        ]);

        respond(['success' => true, 'id' => $db->lastInsertId()], 201);
    }

    // ---------- UPDATE ----------
    if ($method === 'PUT') {
        if (empty($data['id'])) {
            respond(['success' => false, 'error' => 'ID required'], 400);
        }

        $stmt = $db->prepare("
            UPDATE weeks
            SET title = ?, start_date = ?, description = ?, links = ?
            WHERE id = ?
        ");

        $stmt->execute([
            clean($data['title']),
            clean($data['start_date']),
            clean($data['description'] ?? ''),
            json_encode($data['links'] ?? []),
            $data['id']
        ]);

        respond(['success' => true]);
    }

    // ---------- DELETE ----------
    if ($method === 'DELETE') {
        if (!$id) {
            respond(['success' => false, 'error' => 'ID required'], 400);
        }

        $stmt = $db->prepare("DELETE FROM weeks WHERE id = ?");
        $stmt->execute([$id]);

        respond(['success' => true]);
    }

    respond(['success' => false, 'error' => 'Method not allowed'], 405);
}

// =================================================
// ================== COMMENTS ======================
// =================================================

if ($resource === 'comments') {

    // ---------- GET ----------
    if ($method === 'GET') {
        if (!$id) respond(['success' => true, 'data' => []]);

        $stmt = $db->prepare("
            SELECT id, author, text, created_at
            FROM comments_week
            WHERE week_id = ?
            ORDER BY created_at ASC
        ");
        $stmt->execute([$id]);

        respond(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // ---------- POST ----------
    if ($method === 'POST') {
        if (empty($data['week_id']) || empty($data['text'])) {
            respond(['success' => false, 'error' => 'Missing fields'], 400);
        }

        $stmt = $db->prepare("
            INSERT INTO comments_week (week_id, author, text)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $data['week_id'],
            clean($data['author'] ?? 'Student'),
            clean($data['text'])
        ]);

        respond(['success' => true, 'id' => $db->lastInsertId()], 201);
    }

    // ---------- DELETE ----------
    if ($method === 'DELETE') {
        if (!$id) respond(['success' => false, 'error' => 'ID required'], 400);

        $stmt = $db->prepare("DELETE FROM comments_week WHERE id = ?");
        $stmt->execute([$id]);

        respond(['success' => true]);
    }

    respond(['success' => false, 'error' => 'Method not allowed'], 405);
}

// ================== FALLBACK ==================
respond(['success' => false, 'error' => 'Invalid resource'], 400);
