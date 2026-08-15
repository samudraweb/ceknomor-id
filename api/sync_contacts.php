<?php
// api/sync_contacts.php
header('Content-Type: application/json');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['contacts']) || !is_array($input['contacts'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload. Expected contacts array.']);
    exit;
}

$dbClass = new Database();
$db = $dbClass->getConnection();
if (!$db) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

// Get or create a mock user for syncing
$userId = $input['user_id'] ?? 1;

try {
    // Check if user exists, if not create a mock user
    $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    if (!$stmt->fetch()) {
        $stmtMock = $db->prepare("INSERT IGNORE INTO users (id, google_id, name, email) VALUES (?, 'mock_google_id', 'Pengguna Demo', 'demo@gmail.com')");
        $stmtMock->execute([$userId]);
    }

    $validContacts = [];
    foreach ($input['contacts'] as $c) {
        $phone = preg_replace('/[^0-9]/', '', $c['phone'] ?? '');
        $name = trim($c['name'] ?? '');
        if ($phone !== '' && $name !== '' && strlen($phone) >= 10 && strlen($phone) <= 15) {
            $validContacts[] = [$phone, $name];
        }
    }

    $count = count($validContacts);
    if ($count > 0) {
        $db->beginTransaction();
        
        // 1. Fetch existing synced numbers for this user to filter in memory
        $stmtCheck = $db->prepare("SELECT phone_number FROM contact_import_log WHERE user_id = ?");
        $stmtCheck->execute([$userId]);
        $existingPhones = $stmtCheck->fetchAll(PDO::FETCH_COLUMN, 0);
        $existingMap = array_flip($existingPhones);

        $newContactsForThisUser = [];
        foreach ($validContacts as $c) {
            if (!isset($existingMap[$c[0]])) {
                $newContactsForThisUser[] = $c;
                $existingMap[$c[0]] = true; // Prevent duplicates in batch
            }
        }

        // Increment global_contacts vote_count ONLY for new unique user imports
        if (!empty($newContactsForThisUser)) {
            $chunks = array_chunk($newContactsForThisUser, 200);
            foreach ($chunks as $chunk) {
                // Bulk Insert into contact_import_log
                $logClause = [];
                $logParams = [];
                // Bulk Upsert into global_contacts
                $globalClause = [];
                $globalParams = [];

                foreach ($chunk as $c) {
                    $logClause[] = "(?, ?, ?)";
                    $logParams[] = $userId;
                    $logParams[] = $c[0];
                    $logParams[] = $c[1];

                    $globalClause[] = "(?, ?, 1)";
                    $globalParams[] = $c[0];
                    $globalParams[] = $c[1];
                }

                $sqlLog = "INSERT IGNORE INTO contact_import_log (user_id, phone_number, contact_name) VALUES " . implode(', ', $logClause);
                $stmtLog = $db->prepare($sqlLog);
                $stmtLog->execute($logParams);

                $sqlGlobal = "INSERT INTO global_contacts (phone_number, contact_name, vote_count) VALUES " . implode(', ', $globalClause) . " ON DUPLICATE KEY UPDATE vote_count = vote_count + 1";
                $stmtGlobal = $db->prepare($sqlGlobal);
                $stmtGlobal->execute($globalParams);
            }
        }
        
        $db->commit();
        echo json_encode(['success' => true, 'message' => "Synced $count contacts successfully. New additions: " . count($newContactsForThisUser)]);
    } else {
        echo json_encode(['success' => true, 'message' => 'No valid contacts to sync.']);
    }
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Sync failed.', 'detail' => $e->getMessage()]);
}
