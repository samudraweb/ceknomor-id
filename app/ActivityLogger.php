<?php
// app/ActivityLogger.php — Central activity logger

class ActivityLogger {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Log any action in the system.
     * @param string $action     e.g. 'submit_comment', 'approve_comment', 'ban_user'
     * @param string $actorType  'admin' | 'system' | 'api'
     * @param int|null $actorId
     * @param string|null $targetType
     * @param int|null $targetId
     * @param array $payload     extra JSON data
     * @param string $ip
     */
    public function log(
        string $action,
        string $actorType = 'system',
        ?int $actorId = null,
        ?string $targetType = null,
        ?int $targetId = null,
        array $payload = [],
        string $ip = ''
    ): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_logs (actor_type, actor_id, action, target_type, target_id, payload, ip_address)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $actorType,
                $actorId,
                $action,
                $targetType,
                $targetId,
                !empty($payload) ? json_encode($payload) : null,
                $ip
            ]);
        } catch (Exception $e) {
            error_log("ActivityLogger::log failed: " . $e->getMessage());
        }
    }

    public function getRecent(int $limit = 50, string $action = ''): array {
        $sql = "SELECT * FROM activity_logs";
        $params = [];
        if ($action) {
            $sql .= " WHERE action = ?";
            $params[] = $action;
        }
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
