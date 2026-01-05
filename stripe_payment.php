<?php
/**
 * Stripe Payment Handler
 * Handles Stripe checkout sessions and webhook events
 */

require_once 'stripe_config.php';

header('Content-Type: application/json');

// Get database connection
$db = new SQLite3('data.db');

// Get action
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'create_checkout':
        createCheckoutSession();
        break;

    case 'webhook':
        handleWebhook();
        break;

    case 'verify_payment':
        verifyPayment();
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

/**
 * Create Stripe Checkout Session
 */
function createCheckoutSession() {
    global $db;

    $input = json_decode(file_get_contents('php://input'), true);
    $slug = $input['slug'] ?? '';
    $target_url = $input['target_url'] ?? '';

    if (empty($slug) || empty($target_url)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing slug or target_url']);
        return;
    }

    // Get price from config
    $result = $db->query("SELECT value FROM config WHERE key = 'price_sol'");
    $row = $result->fetchArray();
    $price_sol = floatval($row['value'] ?? 0.025);

    // Convert to USD cents (same numeric value, e.g., 0.025 SOL = $0.025 USD = 3 cents)
    $price_usd = round($price_sol * 100); // Convert to cents

    // Minimum charge is $0.50 (50 cents) for Stripe
    if ($price_usd < 50) {
        $price_usd = 50;
    }

    try {
        $checkout_session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Custom Redirect: ' . $slug . '.gudtek.lol',
                        'description' => 'Creates redirect from ' . $slug . '.gudtek.lol to your target URL'
                    ],
                    'unit_amount' => $price_usd,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => 'https://is.gudtek.lol/?payment=success&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => 'https://is.gudtek.lol/?payment=cancelled',
            'metadata' => [
                'slug' => $slug,
                'target_url' => $target_url,
                'payment_method' => 'stripe'
            ]
        ]);

        echo json_encode([
            'success' => true,
            'sessionId' => $checkout_session->id,
            'url' => $checkout_session->url
        ]);

    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

/**
 * Handle Stripe Webhook
 */
function handleWebhook() {
    global $db;

    $payload = @file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

    // For now, we'll verify the event type without webhook secret
    // In production, you should add webhook secret verification

    try {
        $event = json_decode($payload, true);

        if ($event['type'] === 'checkout.session.completed') {
            $session = $event['data']['object'];

            $slug = $session['metadata']['slug'] ?? '';
            $target_url = $session['metadata']['target_url'] ?? '';
            $payment_id = $session['id'];
            $amount = $session['amount_total'] / 100; // Convert from cents to dollars

            if ($slug && $target_url) {
                // Create the redirect in database
                $stmt = $db->prepare('INSERT INTO redirects (slug, target_url, created_at) VALUES (:slug, :target_url, :created_at)');
                $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
                $stmt->bindValue(':target_url', $target_url, SQLITE3_TEXT);
                $stmt->bindValue(':created_at', date('Y-m-d H:i:s'), SQLITE3_TEXT);
                $stmt->execute();

                // Record transaction
                $stmt = $db->prepare('INSERT INTO transactions (slug, transaction_id, amount, payment_method, created_at) VALUES (:slug, :tx_id, :amount, :method, :created_at)');
                $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
                $stmt->bindValue(':tx_id', $payment_id, SQLITE3_TEXT);
                $stmt->bindValue(':amount', $amount, SQLITE3_FLOAT);
                $stmt->bindValue(':method', 'stripe', SQLITE3_TEXT);
                $stmt->bindValue(':created_at', date('Y-m-d H:i:s'), SQLITE3_TEXT);
                $stmt->execute();
            }
        }

        http_response_code(200);
        echo json_encode(['received' => true]);

    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

/**
 * Verify payment status after redirect
 */
function verifyPayment() {
    $session_id = $_GET['session_id'] ?? '';

    if (empty($session_id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing session_id']);
        return;
    }

    try {
        $session = \Stripe\Checkout\Session::retrieve($session_id);

        if ($session->payment_status === 'paid') {
            $slug = $session->metadata->slug ?? '';

            echo json_encode([
                'success' => true,
                'paid' => true,
                'slug' => $slug,
                'redirect_url' => 'https://' . $slug . '.gudtek.lol'
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'paid' => false,
                'status' => $session->payment_status
            ]);
        }

    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
