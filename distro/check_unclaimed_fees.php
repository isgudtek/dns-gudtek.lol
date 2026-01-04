#!/usr/bin/env php
<?php
/**
 * Check Unclaimed PumpPortal Fees
 * Attempts to derive and check the creator fee vault balance
 *
 * Note: PumpPortal doesn't provide a direct API for this.
 * We attempt to collect (dry run) to see if fees are available.
 */

$configFile = __DIR__ . '/pumportal_config.json';
$config = json_decode(file_get_contents($configFile), true);

$walletAddress = $config['wallet_public_key'];

// For now, we'll check the last collection log to estimate
$logFile = __DIR__ . '/royalty_collection_log.json';
$lastCollection = 0;
$lastCollectionTime = 0;

if (file_exists($logFile)) {
    $logs = json_decode(file_get_contents($logFile), true);
    if (!empty($logs)) {
        $lastLog = $logs[0];
        $lastCollectionTime = $lastLog['timestamp'];

        // If we have the signature, we could query the transaction amount
        // For now, we'll return the time since last collection
    }
}

// Return estimated info (since we can't check directly without claiming)
$timeSinceLastClaim = time() - $lastCollectionTime;

echo json_encode([
    'wallet' => $walletAddress,
    'last_collection_timestamp' => $lastCollectionTime,
    'time_since_last_claim_seconds' => $timeSinceLastClaim,
    'estimated_unclaimed' => 0, // Can't determine without claiming
    'note' => 'PumpPortal API does not provide unclaimed balance check. Fees are claimed automatically before each distribution.'
]);
