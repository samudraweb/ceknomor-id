<?php
/**
 * ceknomor.id — Leaderboard API Endpoint
 * GET /api/leaderboard.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../config/database.php';

try {
    $dbClass = new Database();
    $pdo = $dbClass->getConnection();
    if (!$pdo) throw new Exception("Database connection failed");

    // Get top 50 users based on trust_score and helpful_votes
    // We exclude Superadmins or Admins from the public leaderboard if we want,
    // but for now let's just get the top users.
    $stmt = $pdo->query("
        SELECT id, name, avatar_url, role, trust_score, badge, total_reports, total_reviews, helpful_votes 
        FROM users 
        WHERE role = 'user' OR role = 'moderator'
        ORDER BY trust_score DESC, helpful_votes DESC 
        LIMIT 50
    ");
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $leaderboard = [];
    $rank = 1;

    foreach ($users as $u) {
        // Mask the name slightly for privacy if needed, but since it's a community leaderboard, full name is fine.
        $leaderboard[] = [
            'rank' => $rank++,
            'name' => htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8'),
            'avatar_url' => $u['avatar_url'],
            'badge' => $u['badge'],
            'trust_score' => (int)$u['trust_score'],
            'helpful_votes' => (int)$u['helpful_votes'],
            'contributions' => (int)$u['total_reports'] + (int)$u['total_reviews']
        ];
    }

    echo json_encode(['leaderboard' => $leaderboard]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error', 'message' => $e->getMessage()]);
}
