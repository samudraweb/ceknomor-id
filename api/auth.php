<?php
/**
 * ceknomor.id — Google OAuth Authentication
 * GET  /api/auth.php?action=login   → redirect to Google
 * GET  /api/auth.php?action=callback → handle OAuth callback
 * GET  /api/auth.php?action=logout  → destroy session
 * GET  /api/auth.php?action=me      → return current user JSON
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php'; // google/apiclient
}

if (file_exists(__DIR__ . '/../config/env.php')) {
    require_once __DIR__ . '/../config/env.php';
} else {
    die("config/env.php missing. Please copy config/env.example.php to config/env.php and configure it.");
}

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
define('BASE_URL', $protocol . '://' . $host);
define('GOOGLE_REDIRECT_URI',  BASE_URL . '/api/auth.php?action=callback');

$action = $_GET['action'] ?? 'me';

// ── Google Client ─────────────────────────────────────────────
$client = null;
if (!empty(GOOGLE_CLIENT_ID) && class_exists('Google\Client')) {
    $client = new Google\Client();
    $client->setClientId(GOOGLE_CLIENT_ID);
    $client->setClientSecret(GOOGLE_CLIENT_SECRET);
    $client->setRedirectUri(GOOGLE_REDIRECT_URI);
    $client->addScope('email');
    $client->addScope('profile');
    $client->addScope('https://www.googleapis.com/auth/contacts.readonly'); // People API scope
    $client->setAccessType('online');
}

switch ($action) {

  // ── Initiate Login ────────────────────────────────────────
  case 'login':
    if (empty(GOOGLE_CLIENT_ID) || !$client) {
        // MOCK LOGIN FOR LOCAL TESTING
        header('Location: ' . BASE_URL . '/api/auth.php?action=callback&mock=1');
        exit;
    }

    header('Content-Type: text/html');
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;
    $client->setState($state);
    $authUrl = $client->createAuthUrl();
    header('Location: ' . $authUrl);
    exit;

  // ── OAuth Callback ────────────────────────────────────────
  case 'callback':
    header('Content-Type: text/html');

    // MOCK CALLBACK FOR LOCAL TESTING
    if (isset($_GET['mock']) && $_GET['mock'] == '1' && empty(GOOGLE_CLIENT_ID)) {
        // Upsert mock user
        $dbClass = new Database();
        $pdo = $dbClass->getConnection();
        
        $pdo->query("INSERT INTO users (id, google_id, name, email, last_login_at, last_login_ip) 
                     VALUES (999, 'mock_google_123', 'Pengguna Tester', 'tester@localhost', NOW(), '127.0.0.1')
                     ON DUPLICATE KEY UPDATE name = 'Pengguna Tester', last_login_at = NOW()");
                     
        $_SESSION['user_id'] = 999;
        $_SESSION['user_email'] = 'tester@localhost';
        $_SESSION['user_name'] = 'Pengguna Tester';
        
        // Insert Mock Contacts
        $pdo->query("INSERT INTO user_contacts (user_id, phone_number, contact_name) 
                     VALUES (999, '08123456789', 'Budi Penipu')
                     ON DUPLICATE KEY UPDATE contact_name = 'Budi Penipu'");
                     
        $pdo->query("INSERT INTO user_contacts (user_id, phone_number, contact_name) 
                     VALUES (999, '08999999999', 'Toko Online Aman')
                     ON DUPLICATE KEY UPDATE contact_name = 'Toko Online Aman'");

        header('Location: ' . BASE_URL . '/?login=success&mock=1');
        exit;
    }

    // CSRF check
    if (!isset($_GET['state']) || $_GET['state'] !== ($_SESSION['oauth_state'] ?? '')) {
        http_response_code(403);
        die('Invalid state parameter.');
    }

    if (isset($_GET['error'])) {
        header('Location: ' . BASE_URL . '/?error=oauth_denied');
        exit;
    }

    try {
        $token     = $client->fetchAccessTokenWithAuthCode($_GET['code'] ?? '');
        $client->setAccessToken($token);
        $google    = new Google\Service\Oauth2($client);
        $googleUser = $google->userinfo->get();

        // Initialize DB Connection
        $dbClass = new Database();
        $pdo = $dbClass->getConnection();

        // Upsert user in database
        $stmt = $pdo->prepare("
            INSERT INTO users (google_id, name, email, avatar_url, last_login_at, last_login_ip)
            VALUES (:gid, :name, :email, :avatar, NOW(), :ip)
            ON DUPLICATE KEY UPDATE
              name = VALUES(name),
              avatar_url = VALUES(avatar_url),
              last_login_at = NOW(),
              last_login_ip = VALUES(last_login_ip)
        ");
        $stmt->execute([
            'gid'    => $googleUser->getId(),
            'name'   => $googleUser->getName(),
            'email'  => $googleUser->getEmail(),
            'avatar' => $googleUser->getPicture(),
            'ip'     => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        $userId = $pdo->lastInsertId() ?: $pdo
            ->query("SELECT id FROM users WHERE google_id = '{$googleUser->getId()}'")->fetchColumn();

        // ── FETCH CONTACTS VIA PEOPLE API ──
        try {
            $peopleService = new Google\Service\PeopleService($client);
            $optParams = [
                'personFields' => 'names,phoneNumbers',
                'pageSize' => 1000,
            ];
            $connections = $peopleService->people_connections->listPeopleConnections('people/me', $optParams);
            
            if ($connections->getConnections()) {
                $validContacts = [];
                foreach ($connections->getConnections() as $person) {
                    $name = '';
                    $phone = '';
                    
                    if (!empty($person->getNames())) {
                        $name = trim($person->getNames()[0]->getDisplayName());
                    }
                    if (!empty($person->getPhoneNumbers())) {
                        $phone = preg_replace('/[^0-9]/', '', $person->getPhoneNumbers()[0]->getValue());
                    }
                    
                    if ($name !== '' && $phone !== '' && strlen($phone) >= 10 && strlen($phone) <= 15) {
                        $validContacts[] = [$phone, $name];
                    }
                }
                
                if (!empty($validContacts)) {
                    // 1. Fetch existing synced numbers for this user to filter in memory (1 query instead of N)
                    $stmt = $pdo->prepare("SELECT phone_number FROM contact_import_log WHERE user_id = ?");
                    $stmt->execute([$userId]);
                    $existingPhones = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
                    $existingMap = array_flip($existingPhones);

                    $newContactsForThisUser = [];
                    foreach ($validContacts as $c) {
                        if (!isset($existingMap[$c[0]])) {
                            $newContactsForThisUser[] = $c;
                            $existingMap[$c[0]] = true; // Prevent duplicates within the same batch
                        }
                    }

                    if (!empty($newContactsForThisUser)) {
                        $chunks = array_chunk($newContactsForThisUser, 200);
                        foreach ($chunks as $chunk) {
                            // 2. Bulk Insert into contact_import_log
                            $logClause = [];
                            $logParams = [];
                            // 3. Bulk Upsert into global_contacts
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

                            // Execute Bulk Log Insert
                            $sqlLog = "INSERT IGNORE INTO contact_import_log (user_id, phone_number, contact_name) VALUES " . implode(', ', $logClause);
                            $stmtLog = $pdo->prepare($sqlLog);
                            $stmtLog->execute($logParams);

                            // Execute Bulk Global Upsert
                            $sqlGlobal = "INSERT INTO global_contacts (phone_number, contact_name, vote_count) VALUES " . implode(', ', $globalClause) . " ON DUPLICATE KEY UPDATE vote_count = vote_count + 1";
                            $stmtGlobal = $pdo->prepare($sqlGlobal);
                            $stmtGlobal->execute($globalParams);
                        }
                    }
                }
            }
        } catch (Exception $contactEx) {
            error_log("[ceknomor] Failed to fetch contacts: " . $contactEx->getMessage());
            // proceed login even if contacts fail
        }

        $_SESSION['user_id']    = $userId;
        $_SESSION['user_email'] = $googleUser->getEmail();
        $_SESSION['user_name']  = $googleUser->getName();
        $_SESSION['oauth_state'] = null;

        header('Location: ' . BASE_URL . '/?login=success');
        exit;

    } catch (Exception $e) {
        error_log("[ceknomor] oauth error: " . $e->getMessage());
        header('Location: ' . BASE_URL . '/?error=oauth_failed');
        exit;
    }

  // ── Logout ────────────────────────────────────────────────
  case 'logout':
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
    break;

  // ── Current User ──────────────────────────────────────────
  case 'me':
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated.']);
        break;
    }
    $dbClass = new Database();
    $pdo = $dbClass->getConnection();
    
    $stmt = $pdo->prepare("SELECT id, name, email, avatar_url, role, trust_score, badge, total_reports, total_reviews, helpful_votes, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($user ?: ['error' => 'User not found.']);
    break;

  default:
    http_response_code(400);
    echo json_encode(['error' => 'Unknown action.']);
}
