<?php
// app/ReportService.php

class ReportService {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }

    public function submitReport($data, $ip, $fraudAction = 'approve') {
        // Data contains: type, q, bank, category, content, rating, isAnonymous
        $targetType = $data['type'];
        $query = preg_replace('/\D/', '', $data['q'] ?? '');
        if ($targetType === 'phone' && substr($query, 0, 2) === '62' && strlen($query) > 9) {
            $query = '0' . substr($query, 2);
        }
        $bankCode = strtoupper($data['bank'] ?? '');
        $content = strip_tags(trim($data['content'] ?? ''));
        $isAnonymous = !empty($data['isAnonymous']) ? 1 : 0;
        $categorySlug = $data['category'] ?? 'lainnya';

        // Enforce Rating constraints to prevent manipulation
        if ($categorySlug !== 'lainnya') {
            $rating = 1; // Force 1-star for negative reports
        } else {
            $rating = (int)($data['rating'] ?? 5);
            $rating = max(1, min(5, $rating)); // Clamp between 1-5
        }

        // Get Category ID
        $stmt = $this->db->prepare("SELECT id FROM report_categories WHERE slug = ? LIMIT 1");
        $stmt->execute([$categorySlug]);
        $categoryId = $stmt->fetchColumn() ?: 9; // Default to 'lainnya'

        $targetId = null;

        $this->db->beginTransaction();

        try {
            if ($targetType === 'phone') {
                $targetId = $this->getOrCreatePhone($query);
            } else {
                $targetId = $this->getOrCreateRekening($bankCode, $query);
            }

            if (!$targetId) {
                throw new Exception("Invalid target for report.");
            }

            // Insert Comment with auto-approve or flag based on fraud analysis
            $commentStatus = ($fraudAction === 'flag') ? 'flagged' : 'visible';
            $stmt = $this->db->prepare("
                INSERT INTO comments 
                (target_type, target_id, user_id, category_id, content, rating, is_anonymous, ip_address, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $userId = $data['user_id'] ?? null;
            
            // If anonymous flag is set by user intentionally, don't link to profile publicly
            // but we still record the user_id in the DB for trust score purposes.
            // Wait, if we want them to get points, we must record user_id.
            
            $stmt->execute([
                $targetType, $targetId, $userId, $categoryId, $content, $rating, $isAnonymous, $ip, $commentStatus
            ]);

            // Only update aggregates for approved comments
            if ($commentStatus === 'visible') {
                $this->updateAggregates($targetType, $targetId);
                
                // Update Trust Score if User is logged in
                if ($userId) {
                    $this->updateUserTrustScore($userId);
                }
            }

            $this->db->commit();
            return $targetId;  // Return targetId so caller can log

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Report submission failed: " . $e->getMessage());
            return false;
        }
    }

    private function getOrCreatePhone($phone) {
        $stmt = $this->db->prepare("SELECT id FROM phone_numbers WHERE phone_normalized = ? LIMIT 1");
        $stmt->execute([$phone]);
        $id = $stmt->fetchColumn();

        if (!$id) {
            $stmt = $this->db->prepare("
                INSERT INTO phone_numbers (phone_number, phone_normalized, status, security_score) 
                VALUES (?, ?, 'aman', 100)
            ");
            $original = (substr($phone, 0, 2) === '62') ? '0' . substr($phone, 2) : $phone;
            $stmt->execute([$original, $phone]);
            $id = $this->db->lastInsertId();
        }
        return $id;
    }

    private function getOrCreateRekening($bankCode, $accountNormalized) {
        $stmt = $this->db->prepare("SELECT id FROM banks WHERE code = ? LIMIT 1");
        $stmt->execute([$bankCode]);
        $bankId = $stmt->fetchColumn();

        if (!$bankId) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT id FROM bank_accounts WHERE bank_id = ? AND account_normalized = ? LIMIT 1");
        $stmt->execute([$bankId, $accountNormalized]);
        $id = $stmt->fetchColumn();

        if (!$id) {
            $stmt = $this->db->prepare("
                INSERT INTO bank_accounts (bank_id, account_number, account_normalized, status, security_score) 
                VALUES (?, ?, ?, 'aman', 100)
            ");
            $stmt->execute([$bankId, $accountNormalized, $accountNormalized]);
            $id = $this->db->lastInsertId();
        }
        return $id;
    }

    private function updateAggregates($type, $id) {
        // Count total comments & negative reports (rating < 4)
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total, SUM(CASE WHEN rating < 4 THEN 1 ELSE 0 END) as negatives 
            FROM comments 
            WHERE target_type = ? AND target_id = ? AND status = 'visible'
        ");
        $stmt->execute([$type, $id]);
        $stats = $stmt->fetch();

        $totalComments = $stats['total'] ?? 0;
        $totalReports = $stats['negatives'] ?? 0;

        // Simple scoring algorithm: starts at 100, drops by 10 per negative report.
        $score = 100 - ($totalReports * 10);
        if ($score < 0) $score = 0;

        $status = 'aman';
        if ($score <= 40) $status = 'bahaya';
        else if ($score <= 70) $status = 'hatihati';
        else if ($score <= 90) $status = 'waspada';

        $table = $type === 'phone' ? 'phone_numbers' : 'bank_accounts';
        
        $stmt = $this->db->prepare("
            UPDATE {$table} 
            SET security_score = ?, report_count = ?, comment_count = ?, status = ?, last_reported_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$score, $totalReports, $totalComments, $status, $id]);
    }

    private function updateUserTrustScore($userId) {
        // Calculate user totals
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total_reviews, 
                   SUM(CASE WHEN rating < 4 THEN 1 ELSE 0 END) as total_reports
            FROM comments 
            WHERE user_id = ? AND status = 'visible'
        ");
        $stmt->execute([$userId]);
        $stats = $stmt->fetch();

        $totalReviews = (int)($stats['total_reviews'] ?? 0);
        $totalReports = (int)($stats['total_reports'] ?? 0);

        // Simple point system: 10 pts per review, extra 5 pts if it's a negative report
        $trustScore = ($totalReviews * 10) + ($totalReports * 5);

        // Determine Badge
        $badge = 'Pemula';
        if ($trustScore >= 1000) $badge = 'Pahlawan';
        else if ($trustScore >= 500) $badge = 'Kontributor';
        else if ($trustScore >= 100) $badge = 'Penjelajah';

        // Update User
        $stmt = $this->db->prepare("
            UPDATE users 
            SET total_reviews = ?, total_reports = ?, trust_score = ?, badge = ? 
            WHERE id = ?
        ");
        $stmt->execute([$totalReviews, $totalReports, $trustScore, $badge, $userId]);
    }
}
