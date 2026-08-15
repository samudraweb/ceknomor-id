<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/redis.php';
require_once __DIR__ . '/../app/RateLimiter.php';

$dbConfig = new Database();
$db = $dbConfig->getConnection();
if (!$db) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

$redisClient = new RedisClient();
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// ── Rate Limiting ─────────────────────────────────────────────
$limiter = new RateLimiter($redisClient, $ip);
if (!$limiter->allow('vote', 30, 300)) { // 30 votes per 5 mins max
    http_response_code(429);
    echo json_encode(['error' => 'Terlalu banyak permintaan. Silakan tunggu beberapa saat.']);
    exit;
}

// ── Read JSON Body ────────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true);
$commentId = (int)($input['commentId'] ?? 0);
$type = $input['type'] ?? ''; // 'up' or 'down'

if (!$commentId || !in_array($type, ['up', 'down'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request data.']);
    exit;
}

try {
    $db->beginTransaction();

    // Get the comment and its target
    $stmt = $db->prepare("SELECT target_type, target_id FROM comments WHERE id = ?");
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch();

    if (!$comment) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'Komentar tidak ditemukan.']);
        exit;
    }

    // Increment comment helpfulness
    $field = $type === 'up' ? 'helpful_count' : 'not_helpful_count';
    $stmt = $db->prepare("UPDATE comments SET {$field} = {$field} + 1 WHERE id = ?");
    $stmt->execute([$commentId]);

    // If it's an 'up' vote, increment the target's total helpful_count
    if ($type === 'up') {
        $targetTable = $comment['target_type'] === 'phone' ? 'phone_numbers' : 'bank_accounts';
        $stmt = $db->prepare("UPDATE {$targetTable} SET helpful_count = helpful_count + 1 WHERE id = ?");
        $stmt->execute([$comment['target_id']]);
        
        // Also fetch the target query to invalidate cache
        if ($comment['target_type'] === 'phone') {
            $stmt = $db->prepare("SELECT phone_normalized FROM phone_numbers WHERE id = ?");
            $stmt->execute([$comment['target_id']]);
            $normalized = $stmt->fetchColumn();
            $redisClient->del("search:phone:{$normalized}");
        } else {
            $stmt = $db->prepare("SELECT b.code, ba.account_normalized FROM bank_accounts ba JOIN banks b ON ba.bank_id = b.id WHERE ba.id = ?");
            $stmt->execute([$comment['target_id']]);
            $ba = $stmt->fetch();
            if ($ba) {
                $redisClient->del("search:rekening:{$ba['code']}{$ba['account_normalized']}");
            }
        }
    }

    $db->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Terjadi kesalahan sistem.']);
}
