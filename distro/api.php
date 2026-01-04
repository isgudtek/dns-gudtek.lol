<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Distribution system admin wallets (authorized for control)
$ADMIN_WALLETS = [
    'DmXxdBDNxe7Rrq1SReV6FQcG1cswrL2MHxYQPf4J9XdS', // New treasury wallet (pumportal)
    '5Mb8vcPzw2CgEub9NbTUJPQCnoLTRaGiamTkjvETLYzC', // Old distribution wallet
    '819ywRTzmw3Gfei4UgBbmw3FaNRVaPu8Npmz4bcRZFA6'  // Main admin wallet
];
$TREASURY_WALLET = 'DmXxdBDNxe7Rrq1SReV6FQcG1cswrL2MHxYQPf4J9XdS'; // New treasury wallet (pumportal)
$WALLET_FILE = __DIR__ . '/wallet.json';
$HELIUS_API = 'https://mainnet.helius-rpc.com/?api-key=13eb1a93-5010-4ae1-9352-d4171b64a57b';
$CONFIG_FILE = __DIR__ . '/config.json';
$HISTORY_FILE = __DIR__ . '/distribution_history.json';

// Load config
function loadConfig() {
    global $CONFIG_FILE;
    if (file_exists($CONFIG_FILE)) {
        $config = json_decode(file_get_contents($CONFIG_FILE), true);
        // Ensure countdown_start_time exists
        if (!isset($config['countdown_start_time'])) {
            $config['countdown_start_time'] = time();
            saveConfig($config);
        }
        return $config;
    }
    return [
        'interval_seconds' => 86400,
        'last_distribution' => 0,
        'countdown_start_time' => time(),
        'distribution_percentage' => 50,
        'min_holder_balance' => 1000,
        'enabled' => true
    ];
}

// Save config
function saveConfig($config) {
    global $CONFIG_FILE;
    file_put_contents($CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT));
}

// Get treasury balance
function getTreasuryBalance() {
    global $HELIUS_API, $TREASURY_WALLET;

    $payload = json_encode([
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'getBalance',
        'params' => [$TREASURY_WALLET]
    ]);

    $ch = curl_init($HELIUS_API);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['result']['value'] ?? 0;
}

// Get token holders
function getTokenHolders() {
    global $HELIUS_API;

    $db = new SQLite3(__DIR__ . '/../data.db');
    $stmt = $db->prepare('SELECT value FROM config WHERE key = :key');
    $stmt->bindValue(':key', 'token_mint', SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $tokenMint = $row['value'] ?? '';
    $db->close();

    if (empty($tokenMint)) {
        return [];
    }

    $allHolders = [];
    $page = 1;
    $limit = 1000;

    while ($page <= 10) { // Safety limit
        $payload = json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'getTokenAccounts',
            'params' => [
                'mint' => $tokenMint,
                'page' => $page,
                'limit' => $limit
            ]
        ]);

        $ch = curl_init($HELIUS_API);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if (!isset($data['result']['token_accounts']) || empty($data['result']['token_accounts'])) {
            break;
        }

        foreach ($data['result']['token_accounts'] as $account) {
            if ($account['amount'] > 0) {
                $allHolders[] = [
                    'owner' => $account['owner'],
                    'amount' => $account['amount'],
                    'uiAmount' => $account['amount'] / 1000000 // Assuming 6 decimals
                ];
            }
        }

        $page++;
    }

    return $allHolders;
}

// Load distribution history
function loadHistory() {
    global $HISTORY_FILE;
    if (file_exists($HISTORY_FILE)) {
        return json_decode(file_get_contents($HISTORY_FILE), true) ?? [];
    }
    return [];
}

// Save distribution to history
function saveToHistory($distribution) {
    global $HISTORY_FILE;
    $history = loadHistory();
    array_unshift($history, $distribution);
    $history = array_slice($history, 0, 50); // Keep last 50
    file_put_contents($HISTORY_FILE, json_encode($history, JSON_PRETTY_PRINT));
}

// GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'getStatus':
            $config = loadConfig();
            $balance = getTreasuryBalance();
            $balanceSOL = $balance / 1000000000;
            $distributionAmount = ($balance * $config['distribution_percentage'] / 100) / 1000000000;

            $timeUntilNext = 0;
            $timerRunning = $config['timer_running'] ?? false;

            if (!$timerRunning && $config['countdown_paused_at'] > 0) {
                // Timer is paused - calculate time at pause moment
                $elapsed = $config['countdown_paused_at'] - $config['countdown_start_time'];
                $timeUntilNext = max(0, $config['interval_seconds'] - $elapsed);
            } elseif ($config['last_distribution'] > 0) {
                // Calculate based on last distribution
                $nextDistribution = $config['last_distribution'] + $config['interval_seconds'];
                $timeUntilNext = max(0, $nextDistribution - time());
            } elseif (isset($config['countdown_start_time']) && $timerRunning) {
                // Calculate based on countdown start time (only if running)
                $elapsed = time() - $config['countdown_start_time'];
                $timeUntilNext = max(0, $config['interval_seconds'] - $elapsed);
            } else {
                // Fallback
                $timeUntilNext = $config['interval_seconds'];
            }

            // Get token mint from main database
            $db = new SQLite3(__DIR__ . '/../data.db');
            $stmt = $db->prepare('SELECT value FROM config WHERE key = :key');
            $stmt->bindValue(':key', 'token_mint', SQLITE3_TEXT);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);
            $tokenMint = $row['value'] ?? '';
            $db->close();

            echo json_encode([
                'treasury_balance' => $balanceSOL,
                'distribution_amount' => $distributionAmount,
                'time_until_next' => $timeUntilNext,
                'last_distribution' => $config['last_distribution'],
                'interval_seconds' => $config['interval_seconds'],
                'distribution_percentage' => $config['distribution_percentage'],
                'enabled' => $config['enabled'],
                'timer_running' => $timerRunning,
                'token_mint' => $tokenMint
            ]);
            break;

        case 'getHolders':
            $holders = getTokenHolders();
            $totalSupply = array_sum(array_column($holders, 'uiAmount'));

            // Sort by balance
            usort($holders, function($a, $b) {
                return $b['uiAmount'] <=> $a['uiAmount'];
            });

            echo json_encode([
                'holders' => $holders,
                'total_holders' => count($holders),
                'total_supply' => $totalSupply
            ]);
            break;

        case 'getHistory':
            echo json_encode(loadHistory());
            break;

        case 'getDaemonStatus':
            $statusFile = __DIR__ . '/daemon_status.json';
            if (file_exists($statusFile)) {
                $status = json_decode(file_get_contents($statusFile), true);
                $now = time();
                $isRunning = ($now - $status['last_heartbeat']) < 5; // Running if heartbeat within 5 seconds
                echo json_encode([
                    'running' => $isRunning,
                    'last_heartbeat' => $status['last_heartbeat'],
                    'pid' => $status['pid'] ?? 0
                ]);
            } else {
                echo json_encode(['running' => false, 'last_heartbeat' => 0, 'pid' => 0]);
            }
            break;

        case 'getDaemonActivity':
            $activityFile = __DIR__ . '/daemon_activity.json';
            if (file_exists($activityFile)) {
                echo file_get_contents($activityFile);
            } else {
                echo json_encode([]);
            }
            break;

        case 'getRoyaltyStats':
            $statsFile = __DIR__ . '/royalty_stats.json';
            if (file_exists($statsFile)) {
                echo file_get_contents($statsFile);
            } else {
                echo json_encode([
                    'total_collected' => 0,
                    'last_collection_amount' => 0,
                    'last_collection_timestamp' => 0,
                    'collection_count' => 0
                ]);
            }
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

    // Verify admin
    if (!in_array($wallet, $ADMIN_WALLETS)) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    switch ($action) {
        case 'collectRoyalties':
            // Execute royalty collection script
            $scriptPath = __DIR__ . '/collect_royalties.php';
            $output = shell_exec("php " . escapeshellarg($scriptPath) . " 2>&1");

            // Check if collection was successful
            if (strpos($output, '✓ SUCCESS') !== false) {
                // Extract transaction signature
                preg_match('/Transaction Signature: ([A-Za-z0-9]+)/', $output, $matches);
                $signature = $matches[1] ?? null;

                echo json_encode([
                    'success' => true,
                    'message' => 'Royalties collected successfully',
                    'signature' => $signature,
                    'output' => $output
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to collect royalties',
                    'output' => $output
                ]);
            }
            break;

        case 'executeDistribution':
            $config = loadConfig();
            $balance = getTreasuryBalance();
            $holders = getTokenHolders();

            if (count($holders) === 0) {
                echo json_encode(['success' => false, 'error' => 'No token holders found']);
                exit;
            }

            // Sort holders by balance (descending) and exclude the biggest holder (LP)
            usort($holders, function($a, $b) {
                return $b['uiAmount'] <=> $a['uiAmount'];
            });

            // Remove the biggest holder (index 0)
            $excludedHolder = array_shift($holders);
            error_log("Excluded biggest holder (LP): " . $excludedHolder['owner'] . " with balance: " . $excludedHolder['uiAmount']);

            if (count($holders) === 0) {
                echo json_encode(['success' => false, 'error' => 'No eligible holders after excluding LP']);
                exit;
            }

            // Recalculate total supply without the LP
            $totalSupply = array_sum(array_column($holders, 'uiAmount'));
            $distributionAmount = $balance * $config['distribution_percentage'] / 100;
            $distributionSOL = $distributionAmount / 1000000000;

            // Check if enough balance
            if ($distributionAmount < 1000000) { // Less than 0.001 SOL
                echo json_encode(['success' => false, 'error' => 'Insufficient balance for distribution']);
                exit;
            }

            // Calculate distributions per holder (excluding LP)
            $distributions = [];
            $totalDistributedLamports = 0;

            error_log("DEBUG: totalSupply after LP exclusion = $totalSupply");
            error_log("DEBUG: Number of holders after LP = " . count($holders));

            foreach ($holders as $holder) {
                $percentage = ($holder['uiAmount'] / $totalSupply) * 100;
                $holderAmount = ($distributionAmount * $holder['uiAmount']) / $totalSupply;
                $holderSOL = $holderAmount / 1000000000;

                error_log("DEBUG: Holder {$holder['owner']}: amount={$holder['uiAmount']}, holderSOL=$holderSOL");

                if ($holderSOL >= 0.001) { // Min 0.001 SOL
                    $lamports = floor($holderAmount);
                    $distributions[] = [
                        'wallet' => $holder['owner'],
                        'amount_lamports' => $lamports,
                        'amount_sol' => $holderSOL,
                        'percentage' => $percentage
                    ];
                    $totalDistributedLamports += $lamports;
                    error_log("DEBUG: Added holder distribution: $holderSOL SOL to {$holder['owner']}");
                }
            }

            // Calculate remaining balance and send to admin wallet
            $remainingLamports = $balance - $totalDistributedLamports;
            $remainingSOL = $remainingLamports / 1000000000;

            error_log("DEBUG: balance=$balance, totalDistributedLamports=$totalDistributedLamports, remainingLamports=$remainingLamports");
            error_log("DEBUG: distributionAmount=$distributionAmount, holders_after_LP=" . count($holders));

            if ($remainingLamports > 5000) { // At least 0.000005 SOL (for tx fee buffer)
                $distributions[] = [
                    'wallet' => '819ywRTzmw3Gfei4UgBbmw3FaNRVaPu8Npmz4bcRZFA6',
                    'amount_lamports' => floor($remainingLamports - 5000), // Leave 5000 lamports for fees
                    'amount_sol' => ($remainingLamports - 5000) / 1000000000,
                    'percentage' => 0,
                    'is_admin_remainder' => true
                ];
                error_log("Adding admin remainder: " . $remainingSOL . " SOL to 819ywRTzmw3Gfei4UgBbmw3FaNRVaPu8Npmz4bcRZFA6");
            }

            if (count($distributions) === 0) {
                echo json_encode(['success' => false, 'error' => 'No eligible recipients (all amounts too small)']);
                exit;
            }

            // Debug: log distributions array
            error_log("DEBUG: distributions array count = " . count($distributions));
            error_log("DEBUG: distributions = " . json_encode($distributions));

            // Save distributions to temp file
            $tempFile = __DIR__ . '/temp_distribution.json';
            file_put_contents($tempFile, json_encode($distributions, JSON_PRETTY_PRINT));

            // Execute distribution using Node.js script
            $scriptPath = __DIR__ . '/execute_distribution.js';
            $command = "node " . escapeshellarg($scriptPath) . " " . escapeshellarg($tempFile) . " 2>&1";

            error_log("Executing distribution: $command");
            $output = shell_exec($command);
            error_log("Distribution output: $output");

            // Parse results
            $success = false;
            $txResults = [];

            if ($output) {
                // Extract JSON results from output
                if (preg_match('/=== RESULTS JSON ===\s*(\{[\s\S]*\})/m', $output, $matches)) {
                    $results = json_decode($matches[1], true);
                    if ($results && isset($results['transactions'])) {
                        $success = true;
                        $txResults = $results['transactions'];
                    }
                }
            }

            // Clean up temp file
            @unlink($tempFile);

            if ($success) {
                // Calculate actual total sent (including admin remainder)
                $actualTotalSent = 0;
                foreach ($txResults as $tx) {
                    $actualTotalSent += $tx['amount'];
                }

                // Save to history
                $distributionRecord = [
                    'timestamp' => time(),
                    'total_amount' => $actualTotalSent,
                    'recipients' => count($txResults),
                    'distributions' => $txResults,
                    'status' => 'completed'
                ];

                saveToHistory($distributionRecord);

                // Update config
                $config['last_distribution'] = time();
                saveConfig($config);

                echo json_encode([
                    'success' => true,
                    'message' => 'Distribution completed successfully!',
                    'total_amount' => $actualTotalSent,
                    'recipients' => count($txResults),
                    'transactions' => $txResults,
                    'debug_balance' => $balance,
                    'debug_totalDistributed' => $totalDistributedLamports,
                    'debug_remaining' => $remainingLamports
                ]);
            } else {
                // More detailed error
                $errorDetail = 'Failed to parse distribution results.';
                if (empty($output)) {
                    $errorDetail = 'No output from distribution script. Check if Node.js is installed.';
                } elseif (!preg_match('/=== RESULTS JSON ===/m', $output)) {
                    $errorDetail = 'Script did not return valid results format. Check error logs.';
                }

                error_log("Distribution failed. Output: " . $output);
                error_log("Distributions array: " . json_encode($distributions));

                echo json_encode([
                    'success' => false,
                    'error' => $errorDetail,
                    'debug_output' => $output, // Full output for debugging
                    'distributions_count' => count($distributions)
                ]);
            }
            break;

        case 'updateConfig':
            $config = loadConfig();

            if (isset($input['interval_seconds'])) {
                $config['interval_seconds'] = intval($input['interval_seconds']);
            }
            if (isset($input['distribution_percentage'])) {
                $config['distribution_percentage'] = intval($input['distribution_percentage']);
            }
            if (isset($input['enabled'])) {
                $config['enabled'] = (bool)$input['enabled'];
            }

            saveConfig($config);

            // Save token mint to main database config
            if (isset($input['token_mint'])) {
                $tokenMint = trim($input['token_mint']);
                $db = new SQLite3(__DIR__ . '/../data.db');
                $stmt = $db->prepare('INSERT OR REPLACE INTO config (key, value) VALUES (:key, :value)');
                $stmt->bindValue(':key', 'token_mint', SQLITE3_TEXT);
                $stmt->bindValue(':value', $tokenMint, SQLITE3_TEXT);
                $stmt->execute();
                $db->close();
            }

            echo json_encode(['success' => true, 'config' => $config]);
            break;

        case 'startTimer':
            $config = loadConfig();

            // Always allow restart - don't check if already running
            // This enables the loop to restart after distribution

            // Update only timer-related fields, preserve other settings
            $config['countdown_start_time'] = time();
            $config['countdown_paused_at'] = 0;
            $config['timer_running'] = true;
            $config['last_distribution'] = 0; // Reset last distribution for fresh countdown

            // Ensure required fields exist with defaults if missing
            if (!isset($config['interval_seconds'])) $config['interval_seconds'] = 86400;
            if (!isset($config['distribution_percentage'])) $config['distribution_percentage'] = 50;
            if (!isset($config['min_holder_balance'])) $config['min_holder_balance'] = 1000;
            if (!isset($config['enabled'])) $config['enabled'] = true;

            saveConfig($config);

            echo json_encode(['success' => true, 'message' => 'Timer started']);
            break;

        case 'stopTimer':
            $config = loadConfig();

            if (!$config['timer_running']) {
                echo json_encode(['success' => true, 'message' => 'Timer already stopped']);
                break;
            }

            $config['timer_running'] = false;
            $config['countdown_paused_at'] = time();
            saveConfig($config);

            echo json_encode(['success' => true, 'message' => 'Timer stopped']);
            break;

        default:
            echo json_encode(['error' => 'Unknown action']);
    }
}
