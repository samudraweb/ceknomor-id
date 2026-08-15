<?php
// app/SearchService.php

class SearchService {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }

    public function searchPhone($phone) {
        // Clean phone number (keep only digits)
        $normalized = preg_replace('/\D/', '', $phone);
        // Standardize leading 0
        if (substr($normalized, 0, 2) === '62') {
            $normalized = '0' . substr($normalized, 2);
        }

        $stmt = $this->db->prepare("SELECT * FROM phone_numbers WHERE phone_normalized = ? LIMIT 1");
        $stmt->execute([$normalized]);
        $result = $stmt->fetch();

        if (!$result) {
            // Number not found in DB, insert it first
            $this->insertPhone($phone, $normalized);
            
            // Re-fetch the newly inserted row
            $stmt = $this->db->prepare("SELECT * FROM phone_numbers WHERE phone_normalized = ? LIMIT 1");
            $stmt->execute([$normalized]);
            $result = $stmt->fetch();
        }

        // Log to search_history and increment count
        $this->logSearchHistory('phone', $phone, null, $result['id']);
        $this->incrementSearchCount('phone', $result['id']);

        return $this->formatPhoneResponse($result, $phone);
    }

    public function searchRekening($bankCode, $accountNumber) {
        $accountNormalized = preg_replace('/\D/', '', $accountNumber);
        
        $stmt = $this->db->prepare("
            SELECT ba.*, b.name as bank_name 
            FROM bank_accounts ba 
            JOIN banks b ON ba.bank_id = b.id 
            WHERE b.code = ? AND ba.account_normalized = ? 
            LIMIT 1
        ");
        $stmt->execute([$bankCode, $accountNormalized]);
        $result = $stmt->fetch();

        if (!$result) {
            $bankId = $this->getBankId($bankCode);
            if ($bankId) {
                $this->insertRekening($bankId, $accountNumber, $accountNormalized);
                $stmt = $this->db->prepare("SELECT ba.*, b.name as bank_name FROM bank_accounts ba JOIN banks b ON ba.bank_id = b.id WHERE b.code = ? AND ba.account_normalized = ? LIMIT 1");
                $stmt->execute([$bankCode, $accountNormalized]);
                $result = $stmt->fetch();
            } else {
                return $this->getDefaultRekeningResponse($bankCode, $accountNumber);
            }
        }

        $this->logSearchHistory('rekening', $accountNumber, $bankCode, $result['id']);
        $this->incrementSearchCount('rekening', $result['id']);

        return $this->formatRekeningResponse($result, $accountNumber);
    }

    private function incrementSearchCount($type, $id) {
        $table = $type === 'phone' ? 'phone_numbers' : 'bank_accounts';
        $stmt = $this->db->prepare("UPDATE {$table} SET search_count = search_count + 1 WHERE id = ?");
        $stmt->execute([$id]);
    }

    private function logSearchHistory($type, $query, $bankCode = null, $targetId = null) {
        try {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
            $userId = $_SESSION['user_id'] ?? null;
            $stmt = $this->db->prepare("
                INSERT INTO search_history (user_id, search_type, query, bank_code, target_id, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $type, $query, $bankCode, $targetId, $ip]);
        } catch (Exception $e) {}
    }

    private function getBankId($code) {
        $stmt = $this->db->prepare("SELECT id FROM banks WHERE code = ? LIMIT 1");
        $stmt->execute([$code]);
        return $stmt->fetchColumn();
    }

    private function insertPhone($original, $normalized) {
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO phone_numbers 
            (phone_number, phone_normalized, status, security_score, search_count) 
            VALUES (?, ?, 'aman', 100, 1)
        ");
        $stmt->execute([$original, $normalized]);
    }

    private function insertRekening($bankId, $original, $normalized) {
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO bank_accounts 
            (bank_id, account_number, account_normalized, status, security_score, search_count) 
            VALUES (?, ?, ?, 'aman', 100, 1)
        ");
        $stmt->execute([$bankId, $original, $normalized]);
    }

    private function getContactNames($normalizedPhone) {
        try {
            // Build both variants: 08xxx and 628xxx
            $variants = [$normalizedPhone];
            if (substr($normalizedPhone, 0, 1) === '0') {
                // 08xxx → 628xxx
                $variants[] = '62' . substr($normalizedPhone, 1);
            } elseif (substr($normalizedPhone, 0, 2) === '62') {
                // 628xxx → 08xxx
                $variants[] = '0' . substr($normalizedPhone, 2);
            }

            $placeholders = implode(',', array_fill(0, count($variants), '?'));
            $stmt = $this->db->prepare("
                SELECT contact_name
                FROM global_contacts 
                WHERE phone_number IN ($placeholders) 
                ORDER BY vote_count DESC 
                LIMIT 5
            ");
            $stmt->execute($variants);
            $names = [];
            while ($row = $stmt->fetch()) {
                $names[] = htmlspecialchars($row['contact_name'], ENT_QUOTES, 'UTF-8');
            }
            return $names;
        } catch (Exception $e) {
            return [];
        }
    }

    private function getDefaultPhoneResponse($original, $normalized) {
        $contactNames = $this->getContactNames($normalized);
        $ownerName = count($contactNames) > 0 ? $contactNames[0] : null;
        return [
            'q' => $original,
            'type' => 'phone',
            'status' => 'aman',
            'score' => 100,
            'owner_name' => $ownerName,
            'reports' => 0,
            'comments' => 0,
            'searchCount' => 1,
            'helpfulCount' => 0,
            'last_reported' => null,
            'comment_list' => [],
            'insight' => [
                'percentage' => 100,
                'points' => ['Belum ada laporan negatif tercatat.', 'Tetap berhati-hati saat bertransaksi.']
            ]
        ];
    }

    private function getDefaultRekeningResponse($bankCode, $original) {
        return [
            'q' => $original,
            'bank' => $bankCode,
            'type' => 'rekening',
            'status' => 'aman',
            'score' => 100,
            'owner_name' => null,
            'reports' => 0,
            'comments' => 0,
            'searchCount' => 1,
            'helpfulCount' => 0,
            'last_reported' => null,
            'comment_list' => [],
            'insight' => [
                'percentage' => 100,
                'points' => ['Belum ada laporan penipuan untuk rekening ini.', 'Pastikan nama penerima sesuai.']
            ]
        ];
    }

    private function formatPhoneResponse($row, $query) {
        $commentList = $this->getComments('phone', $row['id']);
        $contactNames = $this->getContactNames($row['phone_normalized']);
        $ownerName = count($contactNames) > 0 ? $contactNames[0] : null;
        $otherNames = count($contactNames) > 1 ? array_slice($contactNames, 1) : [];
        
        $score = (int)$row['security_score'];
        $status = $row['status'];
        
        // Cek jika nama kontak mengandung kata-kata berbahaya (di semua 5 nama teratas)
        $isSuspicious = false;
        foreach ($contactNames as $cName) {
            if (preg_match('/penipu|scam|spam|debt|kolektor|pinjol|dc|kasus/i', $cName)) {
                $isSuspicious = true;
                break;
            }
        }

        if ($isSuspicious && $score > 20) {
            $score = 20; // Langsung drop ke 20 (bahaya)
            $status = 'bahaya';
            
            // Sinkronisasi ke DB agar admin & bagian lain ikut terupdate
            try {
                $updateStmt = $this->db->prepare("UPDATE phone_numbers SET security_score = ?, status = ? WHERE id = ?");
                $updateStmt->execute([$score, $status, $row['id']]);
            } catch (Exception $e) {
                // Ignore DB error during read
            }
        }

        return [
            'q' => $query,
            'type' => 'phone',
            'status' => $status,
            'score' => $score,
            'owner_name' => $ownerName,
            'other_names' => $otherNames,
            'reports' => (int)$row['report_count'],
            'comments' => (int)$row['comment_count'],
            'searchCount' => (int)$row['search_count'],
            'helpfulCount' => (int)$row['helpful_count'],
            'last_reported' => $row['last_reported_at'],
            'comment_list' => $commentList,
            'insight' => $this->generateInsights($score, (int)$row['report_count'])
        ];
    }

    private function formatRekeningResponse($row, $query) {
        $commentList = $this->getComments('rekening', $row['id']);
        return [
            'q' => $query,
            'bank' => $row['bank_name'] ?? '',
            'type' => 'rekening',
            'status' => $row['status'],
            'score' => (int)$row['security_score'],
            'reports' => (int)$row['report_count'],
            'comments' => (int)$row['comment_count'],
            'searchCount' => (int)$row['search_count'],
            'helpfulCount' => (int)$row['helpful_count'],
            'last_reported' => $row['last_reported_at'],
            'comment_list' => $commentList,
            'insight' => $this->generateInsights((int)$row['security_score'], (int)$row['report_count'])
        ];
    }

    private function getComments($targetType, $targetId) {
        $stmt = $this->db->prepare("
            SELECT c.*, u.name as user_name, u.avatar_url
            FROM comments c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.target_type = ? AND c.target_id = ? AND c.status = 'visible'
            ORDER BY c.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$targetType, $targetId]);
        $rows = $stmt->fetchAll();
        
        $comments = [];
        foreach ($rows as $r) {
            $comments[] = [
                'id' => $r['id'],
                'author' => htmlspecialchars($r['is_anonymous'] ? 'Pengguna Anonim' : ($r['user_name'] ?? 'User'), ENT_QUOTES, 'UTF-8'),
                'avatar' => $r['is_anonymous'] ? null : $r['avatar_url'],
                'category' => 'Ulasan',
                'content' => htmlspecialchars($r['content'], ENT_QUOTES, 'UTF-8'),
                'created_at' => $r['created_at'],
                'helpfulCount' => (int)$r['helpful_count'],
                'notHelpfulCount' => (int)$r['not_helpful_count']
            ];
        }
        return $comments;
    }

    private function generateInsights($score, $reports) {
        if ($score >= 80) {
            return [
                'percentage' => $score,
                'points' => ['Mayoritas komunitas menganggap aman.', 'Pastikan tetap berhati-hati.']
            ];
        } else if ($score >= 50) {
            return [
                'percentage' => $score,
                'points' => ['Ada beberapa laporan terkait indikasi spam.', 'Dianjurkan waspada.']
            ];
        } else {
            return [
                'percentage' => $score,
                'points' => ["Mendapat $reports laporan penipuan.", 'Disarankan tidak melakukan transaksi.', 'Nomor berisiko tinggi.']
            ];
        }
    }
}
