<?php
// app/FraudDetector.php — Fraud Detection Engine with full logging

class FraudDetector {
    private $db;
    private $ip;
    private $fraudScore = 0;
    private $rules = [];

    public function __construct($db, $ip) {
        $this->db = $db;
        $this->ip = $ip;
    }

    /**
     * Analyze a comment/report before insertion.
     * Returns: ['score' => 0-100, 'action' => 'approve'|'reject'|'flag', 'reasons' => []]
     */
    public function analyze(array $data): array {
        $this->fraudScore = 0;
        $this->rules = [];

        $content = $data['content'] ?? '';
        $targetType = $data['type'] ?? 'phone';
        $query = $data['q'] ?? '';

        // Rule 1: Velocity check — too many submissions from this IP in last 5 min
        $this->checkVelocity();

        // Rule 2: Content spam patterns
        $this->checkContentPatterns($content);

        // Rule 3: Repeated identical content
        $this->checkDuplicateContent($content, $targetType);

        // Rule 4: IP already reported same number many times
        $this->checkIpTargetRepeat($query, $targetType);

        $action = 'approve';
        if ($this->fraudScore >= 80) {
            $action = 'reject';
        } elseif ($this->fraudScore >= 40) {
            $action = 'flag';
        }

        return [
            'score'   => $this->fraudScore,
            'action'  => $action,
            'reasons' => $this->rules
        ];
    }

    private function checkVelocity() {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM activity_logs 
            WHERE ip_address = ? AND action = 'submit_comment' 
            AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ");
        $stmt->execute([$this->ip]);
        $count = (int)$stmt->fetchColumn();

        if ($count >= 10) {
            $this->fraudScore += 60;
            $this->rules[] = ['rule' => 'velocity_burst', 'detail' => "IP submitted {$count} reports in 5 min"];
        } elseif ($count >= 5) {
            $this->fraudScore += 25;
            $this->rules[] = ['rule' => 'velocity_high', 'detail' => "IP submitted {$count} reports in 5 min"];
        }
    }

    private function checkContentPatterns(string $content) {
        $patterns = [
            '/\b(judi|slot|gacor|maxwin|scatter|zeus|togel|deposit|depo)\b/i' => ['judi_keywords', 100],
            '/\d{10,16}/'                                   => ['contains_long_number', 25],
            '/[A-Z]{5,}/'                                   => ['all_caps_content', 15],
            '/(.)\1{5,}/'                                   => ['repeated_chars', 20],
            '/https?:\/\/|www\./i'                          => ['contains_url', 40],
            '/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i' => ['contains_email', 40],
        ];

        foreach ($patterns as $pattern => [$ruleName, $points]) {
            if (preg_match($pattern, $content)) {
                $this->fraudScore += $points;
                $this->rules[] = ['rule' => $ruleName, 'detail' => "Pattern matched in content"];
                if ($this->fraudScore >= 100) break;
            }
        }
    }

    private function checkDuplicateContent(string $content, string $type) {
        $hash = md5(strtolower(trim($content)));
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM comments 
            WHERE target_type = ? AND MD5(LOWER(TRIM(content))) = ?
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$type, $hash]);
        $dupes = (int)$stmt->fetchColumn();

        if ($dupes >= 3) {
            $this->fraudScore += 50;
            $this->rules[] = ['rule' => 'duplicate_content', 'detail' => "Same content submitted {$dupes}x in last hour"];
        }
    }

    private function checkIpTargetRepeat(string $query, string $type) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM activity_logs
            WHERE ip_address = ? AND target_type = ? 
            AND action = 'submit_comment'
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$this->ip, $type]);
        $count = (int)$stmt->fetchColumn();

        if ($count >= 5) {
            $this->fraudScore += 30;
            $this->rules[] = ['rule' => 'ip_target_repeat', 'detail' => "IP reported {$type} {$count}x in last hour"];
        }
    }

    public function logResult(string $targetType, int $targetId, array $result, string $actionTaken) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO fraud_logs 
                (target_type, target_id, ip_address, rule_triggered, fraud_score, details, action_taken)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $primaryRule = !empty($result['reasons']) ? $result['reasons'][0]['rule'] : 'none';
            $stmt->execute([
                $targetType,
                $targetId,
                $this->ip,
                $primaryRule,
                $result['score'],
                json_encode($result['reasons']),
                $actionTaken
            ]);
        } catch (Exception $e) {
            error_log("FraudDetector::logResult failed: " . $e->getMessage());
        }
    }
}
