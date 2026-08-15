<?php
/**
 * ceknomor.id — Trending API Endpoint
 * GET /api/trending.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../config/database.php';

try {
    $dbClass = new Database();
    $pdo = $dbClass->getConnection();
    if (!$pdo) throw new Exception("Database connection failed");

    // Get top 10 trending phones
    $stmtPhones = $pdo->query("
        SELECT phone_number, status, search_count, report_count 
        FROM phone_numbers 
        ORDER BY search_count DESC, report_count DESC 
        LIMIT 10
    ");
    $phones = $stmtPhones->fetchAll(PDO::FETCH_ASSOC);

    // Get top 10 trending banks
    $stmtBanks = $pdo->query("
        SELECT b.code as bank_code, ba.account_number, ba.status, ba.search_count, ba.report_count 
        FROM bank_accounts ba
        JOIN banks b ON ba.bank_id = b.id
        ORDER BY ba.search_count DESC, ba.report_count DESC 
        LIMIT 10
    ");
    $banks = $stmtBanks->fetchAll(PDO::FETCH_ASSOC);

    // Format output to match frontend expectations
    $trendingPhones = [];
    $rank = 1;
    foreach ($phones as $p) {
        $trendingPhones[] = [
            'rank' => $rank++,
            'number' => $p['phone_number'],
            'status' => $p['status'],
            'search_count' => (int)$p['search_count']
        ];
    }

    $trendingBanks = [];
    $rank = 1;
    foreach ($banks as $b) {
        $trendingBanks[] = [
            'rank' => $rank++,
            'bank' => $b['bank_code'],
            'number' => $b['account_number'],
            'status' => $b['status'],
            'search_count' => (int)$b['search_count']
        ];
    }

    echo json_encode([
        'phones' => $trendingPhones,
        'banks' => $trendingBanks
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error', 'message' => $e->getMessage()]);
}
