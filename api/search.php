<?php
/**
 * ceknomor.id — Search API Endpoint
 * GET /api/search.php?type=phone&q=08123456789
 * GET /api/search.php?type=rekening&bank=BCA&q=1234567890
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/redis.php';
require_once __DIR__ . '/../app/RateLimiter.php';
require_once __DIR__ . '/../app/SearchService.php';

// Instantiate Connections
$dbConfig = new Database();
$db = $dbConfig->getConnection();

session_start();

if (!$db) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed. Please ensure MySQL is running.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please login with Google first.']);
    exit;
}
session_write_close(); // Release session file lock immediately for high concurrency

$redisClient = new RedisClient();

// ── Rate Limiting ──────────────────────────────────────────────
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$limiter = new RateLimiter($redisClient, $ip);

if (!$limiter->allow('search', 60, 60)) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many requests. Please wait a moment.']);
    exit;
}

// ── Input Validation ──────────────────────────────────────────
$type  = $_GET['type'] ?? '';
$query = trim($_GET['q'] ?? '');
$bank  = strtoupper(trim($_GET['bank'] ?? ''));

if (!in_array($type, ['phone', 'rekening'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid type. Must be "phone" or "rekening".']);
    exit;
}

if (empty($query)) {
    http_response_code(400);
    echo json_encode(['error' => 'Query cannot be empty.']);
    exit;
}

if ($type === 'phone') {
    $query = preg_replace('/\D/', '', $query);
    // Normalize: 62xxx → 0xxx (e.g., 628123456789 → 08123456789)
    if (substr($query, 0, 2) === '62' && strlen($query) > 9) {
        $query = '0' . substr($query, 2);
    }
    if (strlen($query) < 10 || strlen($query) > 13) {
        http_response_code(400);
        echo json_encode(['error' => 'Nomor telepon harus 10-13 digit.']);
        exit;
    }
} else {
    // Rekening
    if (empty($bank)) {
        http_response_code(400);
        echo json_encode(['error' => 'Bank code is required for rekening search.']);
        exit;
    }
    $query = preg_replace('/\D/', '', $query);
    $bankLengths = [
        'BCA' => 10, 'BNI' => 10, 'BRI' => 15, 'MANDIRI' => 13,
        'CIMB' => 13, 'DANAMON' => 10, 'BTN' => 16, 'BSI' => 10,
        'OCBC' => 12, 'BUKOPIN' => 10, 'MUAMALAT' => 10,
        'BCA SYARIAH' => 10, 'CIMB SYARIAH' => 13, 'BTN SYARIAH' => 10,
        'SINARMAS SYARIAH' => 10
    ];
    $len = strlen($query);
    if (isset($bankLengths[$bank])) {
        if ($len !== $bankLengths[$bank]) {
            http_response_code(400);
            echo json_encode(['error' => "Nomor rekening {$bank} harus {$bankLengths[$bank]} digit."]);
            exit;
        }
    } else {
        if ($len < 10 || $len > 16) {
            http_response_code(400);
            echo json_encode(['error' => 'Nomor rekening harus 10-16 digit.']);
            exit;
        }
    }
}

// ── Redis Cache Check ─────────────────────────────────────────
$cacheKey = "search:{$type}:{$bank}{$query}";
$cached = $redisClient->get($cacheKey);

if ($cached !== false) {
    header('X-Cache: HIT');
    
    // Dynamically increment searchCount in cache for real-time stats
    $data = json_decode($cached, true);
    if (isset($data['searchCount'])) {
        $data['searchCount']++;
        $cached = json_encode($data);
        $redisClient->set($cacheKey, $cached, 600); // refresh TTL
    }
    
    // Sync with database asynchronously (fast update)
    try {
        if ($type === 'phone') {
            $stmt = $db->prepare("UPDATE phone_numbers SET search_count = search_count + 1 WHERE phone_normalized = ?");
            $stmt->execute([$query]);
        } else {
            $stmt = $db->prepare("UPDATE bank_accounts ba JOIN banks b ON ba.bank_id = b.id SET ba.search_count = ba.search_count + 1 WHERE b.code = ? AND ba.account_normalized = ?");
            $stmt->execute([$bank, $query]);
        }
    } catch(PDOException $e) {}

    echo $cached;
    exit;
}

// ── Database Fetch ────────────────────────────────────────────
$service = new SearchService($db);
$response = [];

if ($type === 'phone') {
    $response = $service->searchPhone($query);
} else {
    $response = $service->searchRekening($bank, $query);
}

$jsonResponse = json_encode($response);

// Set Cache for 10 minutes
$redisClient->set($cacheKey, $jsonResponse, 600);

header('X-Cache: MISS');
echo $jsonResponse;
