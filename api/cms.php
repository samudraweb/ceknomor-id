<?php
/**
 * ceknomor.id — Public CMS & Banners API Endpoint
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/redis.php';

try {
    $dbClass = new Database();
    $pdo = $dbClass->getConnection();
    if (!$pdo) throw new Exception("Database connection failed");

    // Optional: Cache with Redis
    $redis = new RedisClient();
    $action = $_GET['action'] ?? '';

    // ==========================================
    // GET ARTICLES
    // ==========================================
    if ($action === 'get_articles') {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 3;
        
        $cacheKey = "cms:articles:{$limit}";
        $cached = $redis->get($cacheKey);
        if ($cached) {
            echo $cached;
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, title, slug, excerpt, view_count, published_at, created_at FROM articles WHERE status = 'published' ORDER BY published_at DESC, created_at DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $articles = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $a) {
            $articles[] = [
                'id' => $a['id'],
                'title' => $a['title'],
                'slug' => $a['slug'],
                'excerpt' => $a['excerpt'],
                'tag' => 'Edukasi', // Default tag for now
                'date' => date('d M Y', strtotime($a['published_at'] ?? $a['created_at'])),
                'views' => $a['view_count']
            ];
        }

        $response = json_encode(['articles' => $articles]);
        $redis->set($cacheKey, $response, 3600); // cache 1 hr
        echo $response;
        exit;
    }

    // ==========================================
    // GET BANNERS
    // ==========================================
    if ($action === 'get_banners') {
        $cacheKey = "cms:banners:active";
        $cached = $redis->get($cacheKey);
        if ($cached) {
            echo $cached;
            exit;
        }

        $stmt = $pdo->query("SELECT id, name, position, type, content, link_url FROM banners WHERE is_active = 1");
        $allBanners = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group by position
        $grouped = [];
        foreach ($allBanners as $b) {
            $pos = $b['position'];
            if (!isset($grouped[$pos])) $grouped[$pos] = [];
            $grouped[$pos][] = $b;
        }

        $response = json_encode(['banners' => $grouped]);
        $redis->set($cacheKey, $response, 3600); // cache 1 hr
        echo $response;
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'message' => $e->getMessage()]);
}
