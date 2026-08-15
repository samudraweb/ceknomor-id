<?php
/**
 * ceknomor.id — Report API Endpoint
 * POST /api/report.php
 * Body (JSON): { type, q, bank, category, content, rating, isAnonymous }
 * 
 * Flow:
 *  1. Validate & sanitize input
 *  2. Server-side anti-spam regex check
 *  3. FraudDetector analysis (velocity, content, duplicate, repeat)
 *  4. Auto-approve if fraud score < 40, flag if 40-79, reject if ≥80
 *  5. Persist to DB via ReportService
 *  6. Log to activity_logs + fraud_logs
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'Unauthorized. Please login.']); exit; }

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/redis.php';
require_once __DIR__ . '/../app/RateLimiter.php';
require_once __DIR__ . '/../app/ReportService.php';
require_once __DIR__ . '/../app/FraudDetector.php';
require_once __DIR__ . '/../app/ActivityLogger.php';

// ── Instantiate Connections ───────────────────────────────────
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
if (!$limiter->allow('report', 10, 300)) {
    http_response_code(429);
    echo json_encode(['error' => 'Terlalu banyak laporan. Silakan tunggu beberapa saat.']);
    exit;
}

// ── Read JSON Body ────────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload.']);
    exit;
}
$input['user_id'] = $_SESSION['user_id'] ?? null;

// ── Input Validation ──────────────────────────────────────────
$content = trim($input['content'] ?? '');
$type    = $input['type'] ?? '';
$q       = trim($input['q'] ?? '');
$qRaw    = preg_replace('/\D/', '', $q);

if ($type === 'phone' && (strlen($qRaw) < 10 || strlen($qRaw) > 13)) {
    http_response_code(400);
    echo json_encode(['error' => 'Nomor telepon harus 10-13 digit.']);
    exit;
}

if (empty($content)) {
    http_response_code(400);
    echo json_encode(['error' => 'Komentar tidak boleh kosong.']);
    exit;
}
if (strlen($content) < 5) {
    http_response_code(400);
    echo json_encode(['error' => 'Komentar terlalu pendek.']);
    exit;
}
if (!in_array($type, ['phone', 'rekening'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipe tidak valid.']);
    exit;
}

// ── Server-side Anti-Spam (Regex Layer 1) ────────────────────
$spamPatterns = [
    '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/' => 'email',
    '/https?:\/\/[^\s]+|www\.[^\s]+/i'                     => 'link website',
    '/\b08\d{8,12}\b/'                                       => 'nomor telepon',
    '/\b(judi|slot|gacor|maxwin|scatter|zeus|togel|deposit|depo)\b/i' => 'kata-kata spam/perjudian'
];
foreach ($spamPatterns as $pattern => $label) {
    if (preg_match($pattern, $content)) {
        http_response_code(400);
        echo json_encode(['error' => "Komentar tidak boleh mengandung {$label}."]);
        exit;
    }
}

// ── Fraud Detection (Layer 2) ─────────────────────────────────
$logger  = new ActivityLogger($db);
$fraud   = new FraudDetector($db, $ip);
$analysis = $fraud->analyze($input);

if ($analysis['action'] === 'reject') {
    // Log the rejection
    $logger->log('auto_reject_comment', 'system', null, $type, null, [
        'fraud_score' => $analysis['score'],
        'reasons'     => $analysis['reasons'],
        'ip'          => $ip
    ], $ip);

    http_response_code(400);
    echo json_encode(['error' => 'Laporan ditolak karena terdeteksi sebagai spam.', 'code' => 'FRAUD_DETECTED']);
    exit;
}

// ── Process Report (Auto-Approve) ────────────────────────────
$reportService = new ReportService($db);
$targetId = $reportService->submitReport($input, $ip, $analysis['action']);

if ($targetId) {
    // Log the submission
    $logger->log('submit_comment', 'api', null, $type, is_int($targetId) ? $targetId : null, [
        'status'      => $analysis['action'] === 'flag' ? 'flagged' : 'approved',
        'fraud_score' => $analysis['score'],
    ], $ip);

    // Log fraud details if flagged
    if ($analysis['action'] === 'flag' || $analysis['score'] > 0) {
        $fraud->logResult($type, is_int($targetId) ? $targetId : 0, $analysis,
            $analysis['action'] === 'flag' ? 'flagged' : 'auto_approved');
    }

    // Invalidate Redis cache
    $bank = strtoupper(trim($input['bank'] ?? ''));
    $queryRaw = preg_replace('/\D/', '', $q);
    if ($type === 'phone' && substr($queryRaw, 0, 2) === '62' && strlen($queryRaw) > 9) {
        $queryRaw = '0' . substr($queryRaw, 2);
    }
    $redisClient->del("search:{$type}:{$bank}{$queryRaw}");

    $msg = $analysis['action'] === 'flag'
        ? 'Laporan diterima dan sedang ditinjau moderator.'
        : 'Laporan berhasil dikirim. Terima kasih atas kontribusi Anda!';

    echo json_encode(['success' => true, 'message' => $msg, 'status' => $analysis['action']]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Terjadi kesalahan saat menyimpan laporan.']);
}
