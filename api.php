<?php
header('Content-Type: application/json');

$db = new SQLite3(__DIR__ . '/data.db');

// Reserved subdomains that cannot be purchased
$RESERVED_DOMAINS = ['is', 'www', 'mail', 'smtp', 'pop', 'imap', 'ftp', 'admin', 'api', 'dev', 'staging', 'test', 'demo', 'blog', 'shop', 'store', 'cdn', 'static', 'assets', 'images', 'img', 'css', 'js'];

// GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'getConfig':
            $config = [];
            $result = $db->query('SELECT key, value FROM config');
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $config[$row['key']] = $row['value'];
            }
            echo json_encode($config);
            break;

        case 'getDomains':
            $stmt = $db->prepare('SELECT slug, target_url, created_at FROM redirects WHERE active = 1 ORDER BY created_at DESC LIMIT 50');
            $result = $stmt->execute();

            $domains = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $domains[] = $row;
            }

            echo json_encode($domains);
            break;

        case 'checkSlug':
            $slug = $_GET['slug'] ?? '';
            $slug = strtolower(trim($slug));

            if (empty($slug) || !preg_match('/^[a-z0-9-]+$/', $slug)) {
                echo json_encode(['available' => false, 'error' => 'Invalid slug format']);
                exit;
            }

            // Check if reserved
            if (in_array($slug, $RESERVED_DOMAINS)) {
                echo json_encode(['available' => false, 'error' => 'Reserved subdomain']);
                exit;
            }

            $stmt = $db->prepare('SELECT COUNT(*) as count FROM redirects WHERE slug = :slug');
            $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);

            echo json_encode(['available' => $row['count'] == 0]);
            break;

        case 'getMyDomains':
            $wallet = $_GET['wallet'] ?? '';

            if (empty($wallet)) {
                echo json_encode([]);
                exit;
            }

            $stmt = $db->prepare('SELECT slug, target_url, created_at FROM redirects WHERE owner_wallet = :wallet AND active = 1 ORDER BY created_at DESC');
            $stmt->bindValue(':wallet', $wallet, SQLITE3_TEXT);
            $result = $stmt->execute();

            $domains = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $domains[] = $row;
            }

            echo json_encode($domains);
            break;

        default:
            echo json_encode(['error' => 'Unknown action']);
    }
}

// POST requests
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    switch ($action) {
        case 'purchase':
            $slug = strtolower(trim($input['slug'] ?? ''));
            $targetUrl = trim($input['targetUrl'] ?? '');
            $wallet = trim($input['wallet'] ?? '');
            $signature = trim($input['signature'] ?? '');
            $currency = $input['currency'] ?? 'sol';

            // Validation
            if (empty($slug) || !preg_match('/^[a-z0-9-]+$/', $slug)) {
                echo json_encode(['success' => false, 'error' => 'Invalid slug format']);
                exit;
            }

            // Check if reserved
            if (in_array($slug, $RESERVED_DOMAINS)) {
                echo json_encode(['success' => false, 'error' => 'This subdomain is reserved']);
                exit;
            }

            // Verify payment with Helius (basic check - transaction exists)
            if (!empty($signature)) {
                $heliusUrl = 'https://mainnet.helius-rpc.com/?api-key=13eb1a93-5010-4ae1-9352-d4171b64a57b';
                $verifyPayload = json_encode([
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'method' => 'getTransaction',
                    'params' => [$signature, ['encoding' => 'json', 'commitment' => 'confirmed']]
                ]);

                $ch = curl_init($heliusUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $verifyPayload);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                $response = curl_exec($ch);
                curl_close($ch);

                $txData = json_decode($response, true);

                // Check if transaction exists (null result means transaction not found)
                if (!isset($txData['result']) || $txData['result'] === null) {
                    error_log('Payment verification failed for signature: ' . $signature . ' - Response: ' . $response);
                    echo json_encode(['success' => false, 'error' => 'Payment not found on blockchain. Please try again.']);
                    exit;
                }
            }

            if (empty($targetUrl) || !filter_var($targetUrl, FILTER_VALIDATE_URL)) {
                echo json_encode(['success' => false, 'error' => 'Invalid URL']);
                exit;
            }

            if (empty($wallet)) {
                echo json_encode(['success' => false, 'error' => 'Wallet address required']);
                exit;
            }

            // Check if slug is available
            $stmt = $db->prepare('SELECT COUNT(*) as count FROM redirects WHERE slug = :slug');
            $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);

            if ($row['count'] > 0) {
                echo json_encode(['success' => false, 'error' => 'Slug already taken']);
                exit;
            }

            // Get price
            $priceKey = $currency === 'sol' ? 'price_sol' : 'price_token';
            $stmt = $db->prepare('SELECT value FROM config WHERE key = :key');
            $stmt->bindValue(':key', $priceKey, SQLITE3_TEXT);
            $result = $stmt->execute();
            $price = $result->fetchArray(SQLITE3_ASSOC)['value'];

            // Insert redirect
            $stmt = $db->prepare('INSERT INTO redirects (slug, target_url, owner_wallet, created_at) VALUES (:slug, :url, :wallet, :time)');
            $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
            $stmt->bindValue(':url', $targetUrl, SQLITE3_TEXT);
            $stmt->bindValue(':wallet', $wallet, SQLITE3_TEXT);
            $stmt->bindValue(':time', time(), SQLITE3_INTEGER);

            if (!$stmt->execute()) {
                echo json_encode(['success' => false, 'error' => 'Failed to create redirect']);
                exit;
            }

            // Record transaction
            $stmt = $db->prepare('INSERT INTO transactions (slug, wallet, amount, currency, tx_signature, created_at) VALUES (:slug, :wallet, :amount, :currency, :sig, :time)');
            $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
            $stmt->bindValue(':wallet', $wallet, SQLITE3_TEXT);
            $stmt->bindValue(':amount', $price, SQLITE3_TEXT);
            $stmt->bindValue(':currency', $currency, SQLITE3_TEXT);
            $stmt->bindValue(':sig', $signature, SQLITE3_TEXT);
            $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
            $stmt->execute();

            echo json_encode(['success' => true, 'slug' => $slug]);
            break;

        case 'deleteMyDomain':
            $slug = $input['slug'] ?? '';
            $wallet = $input['wallet'] ?? '';

            if (empty($slug) || empty($wallet)) {
                echo json_encode(['success' => false, 'error' => 'Missing parameters']);
                exit;
            }

            // Verify ownership
            $stmt = $db->prepare('SELECT owner_wallet FROM redirects WHERE slug = :slug AND active = 1');
            $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);

            if (!$row || $row['owner_wallet'] !== $wallet) {
                echo json_encode(['success' => false, 'error' => 'Not authorized']);
                exit;
            }

            // Deactivate redirect
            $stmt = $db->prepare('UPDATE redirects SET active = 0 WHERE slug = :slug');
            $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);

            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete']);
            }
            break;

        case 'sendMessage':
            $message = trim($input['message'] ?? '');
            $wallet = trim($input['wallet'] ?? 'anonymous');

            if (empty($message)) {
                echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
                exit;
            }

            if (strlen($message) > 1000) {
                echo json_encode(['success' => false, 'error' => 'Message too long (max 1000 characters)']);
                exit;
            }

            // Insert message
            $stmt = $db->prepare('INSERT INTO messages (message, wallet, created_at, status) VALUES (:message, :wallet, :time, "new")');
            $stmt->bindValue(':message', $message, SQLITE3_TEXT);
            $stmt->bindValue(':wallet', $wallet, SQLITE3_TEXT);
            $stmt->bindValue(':time', time(), SQLITE3_INTEGER);

            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to save message']);
            }
            break;

        default:
            echo json_encode(['error' => 'Unknown action']);
    }
}

$db->close();
