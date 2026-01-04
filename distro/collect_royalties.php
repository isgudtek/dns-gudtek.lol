#!/usr/bin/env php
<?php
/**
 * Collect PumpPortal Creator Fees
 * This script collects accumulated creator fees from PumpPortal
 * and sends them to the treasury wallet
 */

$configFile = __DIR__ . '/pumportal_config.json';
$config = json_decode(file_get_contents($configFile), true);

$apiKey = $config['api_key'];
$walletAddress = $config['wallet_public_key'];

// PumpPortal Lightning API endpoint
$apiUrl = "https://pumpportal.fun/api/trade?api-key=" . $apiKey;

// Request body for collecting creator fees
$requestBody = [
    'action' => 'collectCreatorFee',
    'priorityFee' => 0.000001,  // 1 lamport priority fee
    'pool' => 'pump'  // Pump.fun bonding curve
];

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  PUMPORTAL ROYALTY COLLECTION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Wallet: " . $walletAddress . "\n";
echo "Attempting to collect creator fees...\n\n";

// Make API request
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "✗ CURL Error: " . $error . "\n";
    exit(1);
}

if ($httpCode !== 200) {
    echo "✗ HTTP Error: " . $httpCode . "\n";
    echo "Response: " . $response . "\n";
    exit(1);
}

$result = json_decode($response, true);

if (!$result) {
    echo "✗ Invalid JSON response\n";
    echo "Response: " . $response . "\n";
    exit(1);
}

// Check for success
if (isset($result['signature'])) {
    echo "✓ SUCCESS!\n";
    echo "Transaction Signature: " . $result['signature'] . "\n";
    echo "View on Solscan: https://solscan.io/tx/" . $result['signature'] . "\n\n";

    // Wait a moment then check the transaction to get the actual amount collected
    sleep(3);
    $heliusRpc = 'https://mainnet.helius-rpc.com/?api-key=13eb1a93-5010-4ae1-9352-d4171b64a57b';
    $txData = [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'getTransaction',
        'params' => [$result['signature'], ['encoding' => 'jsonParsed', 'maxSupportedTransactionVersion' => 0]]
    ];

    $ch = curl_init($heliusRpc);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($txData));
    $txResponse = curl_exec($ch);
    curl_close($ch);

    $collectedAmount = 0;
    if ($txResponse) {
        $txResult = json_decode($txResponse, true);
        // Try to extract the amount from preBalances and postBalances
        if (isset($txResult['result']['meta']['preBalances'][0]) && isset($txResult['result']['meta']['postBalances'][0])) {
            $collectedAmount = ($txResult['result']['meta']['postBalances'][0] - $txResult['result']['meta']['preBalances'][0]) / 1000000000;
            if ($collectedAmount > 0) {
                echo "Amount Collected: " . number_format($collectedAmount, 9) . " SOL\n";
            }
        }
    }

    // Log the collection
    $logEntry = [
        'timestamp' => time(),
        'signature' => $result['signature'],
        'amount' => $collectedAmount,
        'status' => 'success'
    ];

    $logFile = __DIR__ . '/royalty_collection_log.json';
    $logs = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];
    array_unshift($logs, $logEntry);
    $logs = array_slice($logs, 0, 100); // Keep last 100
    file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));

    // Update stats file for UI display
    $statsFile = __DIR__ . '/royalty_stats.json';
    $stats = file_exists($statsFile) ? json_decode(file_get_contents($statsFile), true) : [
        'total_collected' => 0,
        'last_collection_amount' => 0,
        'last_collection_timestamp' => 0,
        'collection_count' => 0
    ];

    $stats['total_collected'] += $collectedAmount;
    $stats['last_collection_amount'] = $collectedAmount;
    $stats['last_collection_timestamp'] = time();
    $stats['collection_count']++;

    file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT));

    exit(0);
} elseif (isset($result['error'])) {
    echo "✗ API Error: " . $result['error'] . "\n";
    if (isset($result['message'])) {
        echo "Message: " . $result['message'] . "\n";
    }
    exit(1);
} else {
    echo "✗ Unexpected response format\n";
    echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    exit(1);
}
