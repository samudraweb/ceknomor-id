<?php
/**
 * ceknomor.id — Admin CMS & Banners API Endpoint
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../config/database.php';
if (file_exists(__DIR__ . '/../config/env.php')) {
    require_once __DIR__ . '/../config/env.php';
}

session_start();
// Simple admin auth check (assuming index.php sets this)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $dbClass = new Database();
    $pdo = $dbClass->getConnection();
    if (!$pdo) throw new Exception("Database connection failed");

    $action = $_GET['action'] ?? '';

    // ==========================================
    // ARTICLES
    // ==========================================
    if ($action === 'list_articles') {
        $stmt = $pdo->query("SELECT id, title, slug, status, view_count, published_at, created_at FROM articles ORDER BY created_at DESC");
        echo json_encode(['articles' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($action === 'get_article') {
        $id = $_GET['id'] ?? null;
        $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['article' => $stmt->fetch(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($action === 'save_article' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;
        $title = trim($data['title']);
        $slug = trim($data['slug']);
        $excerpt = trim($data['excerpt']);
        $content = trim($data['content']);
        $status = $data['status'] ?? 'draft';
        
        // Ensure slug is unique, simple logic
        if (!$slug) {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title));
        }

        if ($id) {
            $stmt = $pdo->prepare("UPDATE articles SET title=?, slug=?, excerpt=?, content=?, status=? WHERE id=?");
            $stmt->execute([$title, $slug, $excerpt, $content, $status, $id]);
        } else {
            // Hardcoded author_id to 1 for now (admin)
            $stmt = $pdo->prepare("INSERT INTO articles (author_id, title, slug, excerpt, content, status) VALUES (1, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $slug, $excerpt, $content, $status]);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete_article' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM articles WHERE id=?");
            $stmt->execute([$id]);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    // ==========================================
    // BANNERS
    // ==========================================
    if ($action === 'list_banners') {
        $stmt = $pdo->query("SELECT * FROM banners ORDER BY position ASC, created_at DESC");
        echo json_encode(['banners' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($action === 'get_banner') {
        $id = $_GET['id'] ?? null;
        $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['banner' => $stmt->fetch(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($action === 'save_banner' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;
        $name = trim($data['name']);
        $position = $data['position'];
        $type = $data['type'];
        $content = trim($data['content']);
        $link_url = trim($data['link_url'] ?? '');
        $is_active = $data['is_active'] ? 1 : 0;

        if ($id) {
            $stmt = $pdo->prepare("UPDATE banners SET name=?, position=?, type=?, content=?, link_url=?, is_active=? WHERE id=?");
            $stmt->execute([$name, $position, $type, $content, $link_url, $is_active, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO banners (name, position, type, content, link_url, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $position, $type, $content, $link_url, $is_active]);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete_banner' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM banners WHERE id=?");
            $stmt->execute([$id]);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'message' => $e->getMessage()]);
}
