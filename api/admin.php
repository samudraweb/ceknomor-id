<?php
/**
 * ceknomor.id — Admin API Endpoint
 * Handles: dashboard stats, user search, comment moderation, fraud logs, activity logs
 * 
 * Actions (GET):
 *   ?action=stats              → KPI dashboard summary
 *   ?action=users&q=...        → Search users
 *   ?action=comments&status=pending → List comments for moderation
 *   ?action=fraud_logs         → Fraud detection history
 *   ?action=activity_logs      → Admin action log
 *   ?action=search_logs        → Search history
 * 
 * Actions (POST):
 *   {action: 'approve_comment',  id: N}  → Approve comment
 *   {action: 'reject_comment',   id: N}  → Reject comment
 *   {action: 'ban_user',         id: N}  → Ban user
 *   {action: 'lock_number',      type, target_id}  → Lock a number from new reports
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// Simple token auth — replace with proper auth in production
$token = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? $_GET['token'] ?? '';
if ($token !== 'ceknomor_admin_2024') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/ActivityLogger.php';

$dbConfig = new Database();
$db = $dbConfig->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

$logger = new ActivityLogger($db);
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// ── GET endpoints ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'stats';

    switch ($action) {
        case 'stats':
            echo json_encode(getStats($db));
            break;

        case 'users':
            $q = trim($_GET['q'] ?? '');
            echo json_encode(searchUsers($db, $q));
            break;

        case 'comments':
            $status = $_GET['status'] ?? 'flagged';
            if ($status === 'pending') {
                $status = 'flagged';
            }
            $page   = max(1, (int)($_GET['page'] ?? 1));
            echo json_encode(getComments($db, $status, $page));
            break;

        case 'fraud_logs':
            $page = max(1, (int)($_GET['page'] ?? 1));
            echo json_encode(getFraudLogs($db, $page));
            break;

        case 'audit_logs':
            $page = max(1, (int)($_GET['page'] ?? 1));
            echo json_encode(getAuditLogs($db, $page));
            break;

        case 'search_logs':
            $page = max(1, (int)($_GET['page'] ?? 1));
            echo json_encode(getSearchLogs($db, $page));
            break;

        case 'phones':
            $q = trim($_GET['q'] ?? '');
            $page = max(1, (int)($_GET['page'] ?? 1));
            echo json_encode(getPhones($db, $q, $page));
            break;

        case 'rekening':
            $q = trim($_GET['q'] ?? '');
            $page = max(1, (int)($_GET['page'] ?? 1));
            echo json_encode(getRekening($db, $q, $page));
            break;

        case 'phone_detail':
            $id = (int)($_GET['id'] ?? 0);
            echo json_encode(getPhoneDetail($db, $id));
            break;

        case 'community':
            echo json_encode(getCommunityStats($db));
            break;
            
        case 'cms':
            echo json_encode(getCMS($db));
            break;
            
        case 'seo':
            echo json_encode(getSEO($db));
            break;
            
        case 'ads':
            echo json_encode(getAds($db));
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unknown action']);
    }
    exit;
}

// ── POST endpoints ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    switch ($action) {
        case 'approve_comment':
            $id = (int)($input['id'] ?? 0);
            if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }
            $result = approveComment($db, $id);
            if ($result) {
                $logger->log('approve_comment', 'admin', null, 'comment', $id, [], $ip);
                echo json_encode(['success' => true, 'message' => 'Komentar disetujui.']);
            } else {
                http_response_code(500); echo json_encode(['error' => 'Gagal menyetujui.']);
            }
            break;

        case 'reject_comment':
            $id = (int)($input['id'] ?? 0);
            if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }
            $result = rejectComment($db, $id);
            if ($result) {
                $logger->log('reject_comment', 'admin', null, 'comment', $id, [], $ip);
                echo json_encode(['success' => true, 'message' => 'Komentar ditolak.']);
            } else {
                http_response_code(500); echo json_encode(['error' => 'Gagal menolak.']);
            }
            break;

        case 'ban_user':
            $id = (int)($input['id'] ?? 0);
            if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }
            $stmt = $db->prepare("UPDATE users SET is_banned = 1 WHERE id = ?");
            $ok = $stmt->execute([$id]);
            if ($ok) {
                $logger->log('ban_user', 'admin', null, 'user', $id, [], $ip);
                echo json_encode(['success' => true, 'message' => 'Pengguna diblokir.']);
            } else {
                http_response_code(500); echo json_encode(['error' => 'Gagal memblokir.']);
            }
            break;

        case 'unban_user':
            $id = (int)($input['id'] ?? 0);
            if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }
            $stmt = $db->prepare("UPDATE users SET is_banned = 0 WHERE id = ?");
            $ok = $stmt->execute([$id]);
            if ($ok) {
                $logger->log('unban_user', 'admin', null, 'user', $id, [], $ip);
                echo json_encode(['success' => true, 'message' => 'Blokir pengguna dibuka.']);
            } else {
                http_response_code(500); echo json_encode(['error' => 'Gagal membuka blokir.']);
            }
            break;

        case 'lock_number':
            $targetType = $input['target_type'] ?? '';
            $targetId   = (int)($input['target_id'] ?? 0);
            if (!$targetId || !in_array($targetType, ['phone', 'rekening'])) {
                http_response_code(400); echo json_encode(['error' => 'Invalid target']); exit;
            }
            $table = $targetType === 'phone' ? 'phone_numbers' : 'bank_accounts';
            $stmt = $db->prepare("UPDATE {$table} SET is_locked = 1 WHERE id = ?");
            $ok = $stmt->execute([$targetId]);
            if ($ok) {
                $logger->log('lock_number', 'admin', null, $targetType, $targetId, [], $ip);
                echo json_encode(['success' => true, 'message' => 'Nomor dikunci.']);
            } else {
                http_response_code(500); echo json_encode(['error' => 'Gagal mengunci.']);
            }
            break;

        case 'update_status':
            $targetType = $input['target_type'] ?? '';
            $targetId   = (int)($input['target_id'] ?? 0);
            $status     = $input['status'] ?? '';
            $allowed    = ['aman', 'waspada', 'hatihati', 'bahaya'];
            if (!$targetId || !in_array($status, $allowed)) {
                http_response_code(400); echo json_encode(['error' => 'Invalid status']); exit;
            }
            $table = $targetType === 'phone' ? 'phone_numbers' : 'bank_accounts';
            $stmt = $db->prepare("UPDATE {$table} SET status = ? WHERE id = ?");
            $ok = $stmt->execute([$status, $targetId]);
            if ($ok) {
                $logger->log('update_status', 'admin', null, $targetType, $targetId, ['status' => $status], $ip);
                echo json_encode(['success' => true, 'message' => "Status diubah ke {$status}."]);
            } else {
                http_response_code(500); echo json_encode(['error' => 'Gagal update.']);
            }
            break;

        case 'update_phone':
            $id = (int)($input['id'] ?? 0);
            $status = $input['status'] ?? 'aman';
            $score = (int)($input['score'] ?? 100);
            $allowed = ['aman', 'waspada', 'hatihati', 'bahaya'];
            if (!$id || !in_array($status, $allowed) || $score < 0 || $score > 100) {
                http_response_code(400); echo json_encode(['error' => 'Invalid input']); exit;
            }
            $stmt = $db->prepare("UPDATE phone_numbers SET status = ?, security_score = ? WHERE id = ?");
            $ok = $stmt->execute([$status, $score, $id]);
            if ($ok) {
                $logger->log('update_phone', 'admin', null, 'phone', $id, ['status' => $status, 'score' => $score], $ip);
                echo json_encode(['success' => true, 'message' => 'Data nomor berhasil diubah.']);
            } else {
                http_response_code(500); echo json_encode(['error' => 'Gagal update nomor.']);
            }
            break;

        case 'update_phone_contacts':
            $phone = $input['phone_number'] ?? '';
            $updates = $input['updates'] ?? []; // [{old_name, new_name}]
            if (!$phone || !is_array($updates)) {
                http_response_code(400); echo json_encode(['error' => 'Invalid input']); exit;
            }
            try {
                $db->beginTransaction();
                foreach ($updates as $upd) {
                    $old = trim($upd['old_name'] ?? '');
                    $new = trim($upd['new_name'] ?? '');
                    if ($old && $new && $old !== $new) {
                        $stmt = $db->prepare("UPDATE global_contacts SET contact_name = ? WHERE phone_number = ? AND contact_name = ?");
                        $stmt->execute([$new, $phone, $old]);
                    }
                }
                $db->commit();
                $logger->log('update_phone_contacts', 'admin', null, 'phone', 0, ['phone_number' => $phone], $ip);
                echo json_encode(['success' => true, 'message' => 'Kontak nomor berhasil diperbarui.']);
            } catch(Exception $e) {
                $db->rollBack();
                http_response_code(500); echo json_encode(['error' => 'Gagal update kontak.']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unknown action']);
    }
    exit;
}

// ═══════════════════════════════════════════════════════════════
// Helper Functions
// ═══════════════════════════════════════════════════════════════

function getStats($db): array {
    $stats = [];
    
    // Fast approximate counts for large tables
    $getApprox = function($table) use ($db) {
        $stmt = $db->query("SELECT TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'");
        return (int)$stmt->fetchColumn();
    };

    $stats['total_phones']   = $getApprox('phone_numbers');
    $stats['total_rekening'] = $getApprox('bank_accounts');
    $stats['total_comments'] = $getApprox('comments');
    $stats['total_users']    = $getApprox('users');
    
    // Indexed COUNTs for specific conditions
    $tables = [
        'pending_comments'=> "SELECT COUNT(*) FROM comments WHERE status = 'flagged'",
        'total_fraud_flags'=> "SELECT COUNT(*) FROM fraud_logs WHERE fraud_score >= 40",
        'bahaya_phones'   => "SELECT COUNT(*) FROM phone_numbers WHERE status = 'bahaya'",
        'searches_today'  => "SELECT COUNT(*) FROM search_history WHERE created_at >= CURDATE()",
    ];

    foreach ($tables as $key => $sql) {
        try {
            $stmt = $db->query($sql);
            $stats[$key] = (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            $stats[$key] = 0;
        }
    }

    // Recent activity (last 24h)
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM comments WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stats['comments_24h'] = (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        $stats['comments_24h'] = 0;
    }

    // New Users
    $stmt = $db->query("SELECT name, trust_score, created_at FROM users ORDER BY id DESC LIMIT 5");
    $stats['new_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Top Searches
    $stmt = $db->query("SELECT phone_number, search_count, status FROM phone_numbers ORDER BY search_count DESC LIMIT 6");
    $stats['top_searches'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Live Feed (audit_logs)
    $stmt = $db->query("SELECT a.action, u.name as admin_name, a.target_type, a.target_id, a.created_at FROM audit_logs a LEFT JOIN users u ON a.admin_id = u.id ORDER BY a.id DESC LIMIT 15");
    $stats['live_feed'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Chart Data (7 Days)
    // Chart Data (7 Days)
    $chartData = ['labels' => [], 'searches' => [], 'reports' => [], 'revenue' => []];
    $startDate = date('Y-m-d 00:00:00', strtotime("-6 days"));
    
    // Aggregate searches in one query
    $stmt = $db->query("SELECT DATE(created_at) as d, COUNT(*) as c FROM search_history WHERE created_at >= '$startDate' GROUP BY DATE(created_at)");
    $searchesByDate = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
    
    // Aggregate reports in one query
    $stmt = $db->query("SELECT DATE(created_at) as d, COUNT(*) as c FROM reports WHERE created_at >= '$startDate' GROUP BY DATE(created_at)");
    $reportsByDate = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $chartData['labels'][] = date('d M', strtotime($d));
        
        $chartData['searches'][] = (int)($searchesByDate[$d] ?? 0);
        $chartData['reports'][] = (int)($reportsByDate[$d] ?? 0);
        $chartData['revenue'][] = 0;
    }
    $stats['chart_data'] = $chartData;

    return $stats;
}

function searchUsers($db, $q): array {
    $sql = "SELECT id, google_id, name, email, role, trust_score, badge, is_banned, created_at FROM users";
    $cSql = "SELECT COUNT(*) FROM users";
    $params = [];
    if ($q) {
        $sql .= " WHERE name LIKE ? OR email LIKE ?";
        $cSql .= " WHERE name LIKE ? OR email LIKE ?";
        $q = "%$q%";
        $params = [$q, $q];
    }
    $sql .= " ORDER BY id DESC LIMIT 50";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    if ($q) {
        $cStmt = $db->prepare($cSql);
        $cStmt->execute($params);
        $total = (int)$cStmt->fetchColumn();
    } else {
        $total = (int)$db->query("SELECT TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'")->fetchColumn();
    }

    return ['users' => $users, 'total' => $total];
}

function getPhones($db, $q, $page = 1, $limit = 50): array {
    $offset = ($page - 1) * $limit;
    $sql = "SELECT id, phone_number, status, security_score, report_count, search_count FROM phone_numbers";
    $params = [];
    if ($q) {
        $sql .= " WHERE phone_number LIKE ?";
        $params[] = "%$q%";
    }
    $sql .= " ORDER BY search_count DESC, id DESC LIMIT $limit OFFSET $offset";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Fetch top 5 contacts for each phone
    foreach ($data as &$row) {
        $cStmt = $db->prepare("
            SELECT contact_name, vote_count as count 
            FROM global_contacts 
            WHERE phone_number = ? 
            ORDER BY count DESC 
            LIMIT 5
        ");
        $cStmt->execute([$row['phone_number']]);
        $row['contacts'] = $cStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Count total
    if ($q) {
        $cSql = "SELECT COUNT(*) FROM phone_numbers WHERE phone_number LIKE ?";
        $cStmt = $db->prepare($cSql);
        $cStmt->execute($params);
        $total = (int)$cStmt->fetchColumn();
    } else {
        $total = (int)$db->query("SELECT TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'phone_numbers'")->fetchColumn();
    }

    return [
        'data' => $data,
        'total' => $total,
        'page' => $page,
        'limit' => $limit
    ];
}

function getRekening($db, $q, $page = 1, $limit = 50): array {
    $offset = ($page - 1) * $limit;
    $sql = "SELECT ba.id, ba.account_number, ba.status, ba.security_score, ba.report_count, ba.search_count, b.name as bank_name 
            FROM bank_accounts ba 
            LEFT JOIN banks b ON ba.bank_id = b.id";
    $params = [];
    if ($q) {
        $sql .= " WHERE ba.account_number LIKE ?";
        $params[] = "%$q%";
    }
    $sql .= " ORDER BY ba.search_count DESC, ba.id DESC LIMIT $limit OFFSET $offset";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Count total
    if ($q) {
        $cSql = "SELECT COUNT(*) FROM bank_accounts WHERE account_number LIKE ?";
        $cStmt = $db->prepare($cSql);
        $cStmt->execute($params);
        $total = (int)$cStmt->fetchColumn();
    } else {
        $total = (int)$db->query("SELECT TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bank_accounts'")->fetchColumn();
    }

    return [
        'data' => $data,
        'total' => $total,
        'page' => $page,
        'limit' => $limit
    ];
}

function getComments($db, string $status, int $page): array {
    $perPage = 20;
    $offset = ($page - 1) * $perPage;
    
    $stmt = $db->prepare("
        SELECT c.*, 
               pn.phone_number,
               ba.account_number,
               b.name as bank_name,
               rc.label as category_name
        FROM comments c
        LEFT JOIN phone_numbers pn ON c.target_type = 'phone' AND c.target_id = pn.id
        LEFT JOIN bank_accounts ba ON c.target_type = 'rekening' AND c.target_id = ba.id
        LEFT JOIN banks b ON ba.bank_id = b.id
        LEFT JOIN report_categories rc ON c.category_id = rc.id
        WHERE c.status = ?
        ORDER BY c.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$status, $perPage, $offset]);
    $rows = $stmt->fetchAll();

    $countStmt = $db->prepare("SELECT COUNT(*) FROM comments WHERE status = ?");
    $countStmt->execute([$status]);
    $total = (int)$countStmt->fetchColumn();

    return ['comments' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
}

function getFraudLogs($db, int $page): array {
    $perPage = 30;
    $offset = ($page - 1) * $perPage;
    $stmt = $db->prepare("SELECT * FROM fraud_logs ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$perPage, $offset]);
    $rows = $stmt->fetchAll();

    $total = (int)$db->query("SELECT COUNT(*) FROM fraud_logs")->fetchColumn();
    return ['logs' => $rows, 'total' => $total, 'page' => $page];
}

function getAuditLogs($db, int $page): array {
    $perPage = 30;
    $offset = ($page - 1) * $perPage;
    $stmt = $db->prepare("SELECT a.*, u.name as admin_name FROM audit_logs a LEFT JOIN users u ON a.admin_id = u.id ORDER BY a.created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$perPage, $offset]);
    $rows = $stmt->fetchAll();

    $total = (int)$db->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
    return ['logs' => $rows, 'total' => $total, 'page' => $page];
}

function getSearchLogs($db, int $page): array {
    $perPage = 30;
    $offset = ($page - 1) * $perPage;
    $stmt = $db->prepare("SELECT * FROM search_history ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$perPage, $offset]);
    $rows = $stmt->fetchAll();

    $total = (int)$db->query("SELECT COUNT(*) FROM search_history")->fetchColumn();
    return ['logs' => $rows, 'total' => $total, 'page' => $page];
}

function getPhoneDetail($db, int $id): array {
    $stmt = $db->prepare("SELECT * FROM phone_numbers WHERE id = ?");
    $stmt->execute([$id]);
    $phone = $stmt->fetch();

    $commentsStmt = $db->prepare("SELECT * FROM comments WHERE target_type = 'phone' AND target_id = ? ORDER BY created_at DESC LIMIT 20");
    $commentsStmt->execute([$id]);
    $comments = $commentsStmt->fetchAll();

    return ['phone' => $phone, 'comments' => $comments];
}

function approveComment($db, int $id): bool {
    $info = $db->prepare("SELECT user_id, target_type, target_id FROM comments WHERE id = ?");
    $info->execute([$id]);
    $row = $info->fetch();
    
    if ($row) {
        $stmt = $db->prepare("UPDATE comments SET status = 'visible' WHERE id = ?");
        $ok = $stmt->execute([$id]);
        if ($ok) {
            updateAggregates($db, $row['target_type'], $row['target_id']);
            // Tambahkan Trust Score
            if (!empty($row['user_id'])) {
                $db->prepare("UPDATE users SET trust_score = trust_score + 2 WHERE id = ?")->execute([$row['user_id']]);
            }
        }
        return (bool)$ok;
    }
    return false;
}

function rejectComment($db, int $id): bool {
    $info = $db->prepare("SELECT user_id FROM comments WHERE id = ?");
    $info->execute([$id]);
    $row = $info->fetch();

    $stmt = $db->prepare("UPDATE comments SET status = 'removed' WHERE id = ?");
    $ok = $stmt->execute([$id]);
    if ($ok && $row && !empty($row['user_id'])) {
        // Kurangi Trust Score karena laporan ditolak/spam
        $db->prepare("UPDATE users SET trust_score = trust_score - 5 WHERE id = ?")->execute([$row['user_id']]);
    }
    return (bool)$ok;
}

function updateAggregates($db, $type, $id) {
    $stmt = $db->prepare("
        SELECT COUNT(*) as total, SUM(CASE WHEN rating < 4 THEN 1 ELSE 0 END) as negatives 
        FROM comments 
        WHERE target_type = ? AND target_id = ? AND status = 'visible'
    ");
    $stmt->execute([$type, $id]);
    $stats = $stmt->fetch();
    $totalComments = $stats['total'] ?? 0;
    $totalReports = $stats['negatives'] ?? 0;
    $score = max(0, 100 - ($totalReports * 10));
    $status = $score <= 40 ? 'bahaya' : ($score <= 70 ? 'hatihati' : ($score <= 90 ? 'waspada' : 'aman'));
    $table = $type === 'phone' ? 'phone_numbers' : 'bank_accounts';
    $upd = $db->prepare("UPDATE {$table} SET security_score = ?, report_count = ?, comment_count = ?, status = ?, last_reported_at = NOW() WHERE id = ?");
    $upd->execute([$score, $totalReports, $totalComments, $status, $id]);
}
function getCommunityStats($db) {
    $totalReviews = $db->query("SELECT TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'comments'")->fetchColumn();
    $helpfulVotes = $db->query("SELECT SUM(helpful_votes) FROM users")->fetchColumn();
    $activeContributors = $db->query("SELECT COUNT(*) FROM users WHERE total_reviews > 0")->fetchColumn();
    $topContributors = $db->query("SELECT name, trust_score, total_reviews as kontribusi FROM users ORDER BY total_reviews DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    return ['total_reviews' => (int)$totalReviews, 'helpful_votes' => (int)$helpfulVotes, 'active_contributors' => (int)$activeContributors, 'top_contributors' => $topContributors];
}

function getCMS($db) {
    return $db->query("SELECT * FROM articles ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
}

function getSEO($db) {
    return $db->query("SELECT * FROM seo_settings LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
}

function getAds($db) {
    return $db->query("SELECT * FROM banners ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
}
