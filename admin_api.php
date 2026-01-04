<?php
header('Content-Type: application/json');

$ADMIN_WALLET = '819ywRTzmw3Gfei4UgBbmw3FaNRVaPu8Npmz4bcRZFA6';
$db = new SQLite3(__DIR__ . '/data.db');

// Verify admin wallet
function verifyAdmin($wallet) {
    global $ADMIN_WALLET;
    return $wallet === $ADMIN_WALLET;
}

// GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    $wallet = $_GET['wallet'] ?? '';

    if (!verifyAdmin($wallet)) {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    switch ($action) {
        case 'getRedirects':
            $result = $db->query('SELECT * FROM redirects ORDER BY created_at DESC');
            $redirects = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $redirects[] = $row;
            }
            echo json_encode($redirects);
            break;

        case 'getTransactions':
            $result = $db->query('SELECT * FROM transactions ORDER BY created_at DESC LIMIT 100');
            $transactions = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $transactions[] = $row;
            }
            echo json_encode($transactions);
            break;

        case 'getMessages':
            $result = $db->query('SELECT * FROM messages ORDER BY created_at DESC LIMIT 100');
            $messages = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $messages[] = $row;
            }
            echo json_encode($messages);
            break;

        default:
            echo json_encode(['error' => 'Unknown action']);
    }
}

// POST requests
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $wallet = $input['wallet'] ?? '';

    if (!verifyAdmin($wallet)) {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    switch ($action) {
        case 'updateConfig':
            $config = $input['config'] ?? [];

            foreach ($config as $key => $value) {
                $stmt = $db->prepare('INSERT OR REPLACE INTO config (key, value) VALUES (:key, :value)');
                $stmt->bindValue(':key', $key, SQLITE3_TEXT);
                $stmt->bindValue(':value', $value, SQLITE3_TEXT);
                $stmt->execute();
            }

            echo json_encode(['success' => true]);
            break;

        case 'deleteRedirect':
            $id = $input['id'] ?? 0;

            $stmt = $db->prepare('UPDATE redirects SET active = 0 WHERE id = :id');
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);

            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete']);
            }
            break;

        case 'createRedirect':
            $slug = strtolower(trim($input['slug'] ?? ''));
            $targetUrl = trim($input['targetUrl'] ?? '');

            // Validation
            if (empty($slug) || !preg_match('/^[a-z0-9-]+$/', $slug)) {
                echo json_encode(['success' => false, 'error' => 'Invalid slug format']);
                exit;
            }

            // Allow empty URL for reserved domains
            if (!empty($targetUrl) && !filter_var($targetUrl, FILTER_VALIDATE_URL)) {
                echo json_encode(['success' => false, 'error' => 'Invalid URL']);
                exit;
            }

            // Check if slug already exists (active or deleted)
            $stmt = $db->prepare('SELECT * FROM redirects WHERE slug = :slug');
            $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
            $result = $stmt->execute();
            $existing = $result->fetchArray(SQLITE3_ASSOC);

            if ($existing) {
                if ($existing['active'] == 1) {
                    echo json_encode(['success' => false, 'error' => 'Slug already exists and is active']);
                    exit;
                } else {
                    // Reactivate deleted domain with new URL
                    $stmt = $db->prepare('UPDATE redirects SET target_url = :url, owner_wallet = :wallet, created_at = :time, active = 1 WHERE slug = :slug');
                    $stmt->bindValue(':url', $targetUrl, SQLITE3_TEXT);
                    $stmt->bindValue(':wallet', $wallet, SQLITE3_TEXT);
                    $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
                    $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);

                    if ($stmt->execute()) {
                        $message = empty($targetUrl) ? 'Domain reserved successfully (reactivated)' : 'Redirect created successfully (reactivated)';
                        echo json_encode(['success' => true, 'slug' => $slug, 'message' => $message]);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Failed to reactivate redirect']);
                    }
                    break;
                }
            }

            // Create new redirect (URL can be empty for reserved domains)
            $stmt = $db->prepare('INSERT INTO redirects (slug, target_url, owner_wallet, created_at, active) VALUES (:slug, :url, :wallet, :time, 1)');
            $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
            $stmt->bindValue(':url', $targetUrl, SQLITE3_TEXT);
            $stmt->bindValue(':wallet', $wallet, SQLITE3_TEXT);
            $stmt->bindValue(':time', time(), SQLITE3_INTEGER);

            if ($stmt->execute()) {
                $message = empty($targetUrl) ? 'Domain reserved successfully' : 'Redirect created successfully';
                echo json_encode(['success' => true, 'slug' => $slug, 'message' => $message]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to create redirect']);
            }
            break;

        case 'updateRedirect':
            $id = $input['id'] ?? 0;
            $targetUrl = trim($input['targetUrl'] ?? '');

            // Validate URL if provided
            if (!empty($targetUrl) && !filter_var($targetUrl, FILTER_VALIDATE_URL)) {
                echo json_encode(['success' => false, 'error' => 'Invalid URL']);
                exit;
            }

            // Check if redirect exists and is active
            $stmt = $db->prepare('SELECT * FROM redirects WHERE id = :id AND active = 1');
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $result = $stmt->execute();
            $redirect = $result->fetchArray(SQLITE3_ASSOC);

            if (!$redirect) {
                echo json_encode(['success' => false, 'error' => 'Redirect not found or already deleted']);
                exit;
            }

            // Update redirect
            $stmt = $db->prepare('UPDATE redirects SET target_url = :url WHERE id = :id');
            $stmt->bindValue(':url', $targetUrl, SQLITE3_TEXT);
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);

            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update redirect']);
            }
            break;

        default:
            echo json_encode(['error' => 'Unknown action']);
    }
}

$db->close();
