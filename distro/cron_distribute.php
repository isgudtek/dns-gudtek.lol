#!/usr/bin/env php
<?php
// Server-side distribution cron job
// Add to crontab: * * * * * /var/www/gudtek.lol/is/distro/cron_distribute.php

$CONFIG_FILE = __DIR__ . '/config.json';
$ADMIN_WALLET = '5Mb8vcPzw2CgEub9NbTUJPQCnoLTRaGiamTkjvETLYzC';

function loadConfig() {
    global $CONFIG_FILE;
    if (file_exists($CONFIG_FILE)) {
        return json_decode(file_get_contents($CONFIG_FILE), true);
    }
    return null;
}

function executeDistribution() {
    global $ADMIN_WALLET;

    $ch = curl_init('http://localhost/is/distro/api.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'action' => 'executeDistribution',
        'wallet' => $ADMIN_WALLET
    ]));

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

function restartTimer() {
    global $ADMIN_WALLET;

    $ch = curl_init('http://localhost/is/distro/api.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'action' => 'startTimer',
        'wallet' => $ADMIN_WALLET
    ]));

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

// Main logic
$config = loadConfig();

if (!$config) {
    error_log("Cron: No config found");
    exit(1);
}

// Check if timer is running and enabled
if (!$config['timer_running'] || !$config['enabled']) {
    exit(0); // Silent exit if disabled
}

// Calculate time until next distribution
$now = time();
$elapsed = $now - $config['countdown_start_time'];
$timeUntilNext = $config['interval_seconds'] - $elapsed;

// If time is up, execute distribution
if ($timeUntilNext <= 0) {
    error_log("Cron: Time reached, executing distribution...");

    $result = executeDistribution();

    if ($result && isset($result['success'])) {
        if ($result['success']) {
            error_log("Cron: Distribution successful - " . $result['total_amount'] . " SOL to " . $result['recipients'] . " holders");
        } else {
            error_log("Cron: Distribution failed - " . $result['error']);
        }
    }

    // Always restart timer for next cycle
    error_log("Cron: Restarting timer for next cycle...");
    restartTimer();
}
