<?php
// Get token mint from config
$db = new SQLite3(__DIR__ . '/data.db');
$stmt = $db->prepare('SELECT value FROM config WHERE key = :key');
$stmt->bindValue(':key', 'token_mint', SQLITE3_TEXT);
$result = $stmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);
$tokenMint = $row['value'] ?? '';
$db->close();

// Pagination settings
$perPage = 50;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Fetch ALL holders from Helius
$holders = [];
$totalSupply = 0;
$totalHolders = 0;
$error = null;

if (!empty($tokenMint) && isset($_GET['fetch'])) {
    $heliusUrl = 'https://mainnet.helius-rpc.com/?api-key=13eb1a93-5010-4ae1-9352-d4171b64a57b';

    // Get token supply
    $supplyPayload = json_encode([
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'getTokenSupply',
        'params' => [$tokenMint]
    ]);

    $ch = curl_init($heliusUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $supplyPayload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    curl_close($ch);

    $supplyData = json_decode($response, true);
    $totalSupply = $supplyData['result']['value']['uiAmount'] ?? 0;

    // Fetch ALL token holders using Helius DAS API
    $allHolders = [];
    $apiPage = 1;
    $limit = 1000; // Max per request

    while (true) {
        $holdersPayload = json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'getTokenAccounts',
            'params' => [
                'mint' => $tokenMint,
                'page' => $apiPage,
                'limit' => $limit
            ]
        ]);

        $ch = curl_init($heliusUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $holdersPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        curl_close($ch);

        $holdersData = json_decode($response, true);

        // Log first page response
        if ($apiPage === 1) {
            error_log('Helius getTokenAccounts Response: ' . substr($response, 0, 500));
        }

        if (isset($holdersData['error'])) {
            $error = 'API Error: ' . ($holdersData['error']['message'] ?? 'Unknown error');
            error_log('Helius Error: ' . json_encode($holdersData['error']));
            break;
        }

        if (!isset($holdersData['result']['token_accounts']) || empty($holdersData['result']['token_accounts'])) {
            break; // No more results
        }

        // Process this page of results
        foreach ($holdersData['result']['token_accounts'] as $account) {
            if ($account['amount'] > 0) {
                $allHolders[] = [
                    'owner' => $account['owner'],
                    'address' => $account['address'],
                    'amount' => $account['amount'],
                    'decimals' => 6 // Assuming 6 decimals, adjust if needed
                ];
            }
        }

        $apiPage++;

        // Safety limit to prevent infinite loops
        if ($apiPage > 100) {
            error_log('Warning: Stopped fetching after 100 pages (100,000 accounts)');
            break;
        }
    }

    error_log('Total token accounts fetched: ' . count($allHolders));

    // Convert amounts to UI amounts and sort by balance
    foreach ($allHolders as &$holder) {
        $holder['uiAmount'] = $holder['amount'] / pow(10, $holder['decimals']);
    }
    unset($holder);

    usort($allHolders, function($a, $b) {
        return $b['uiAmount'] <=> $a['uiAmount'];
    });

    $totalHolders = count($allHolders);

    // Paginate for display
    $offset = ($page - 1) * $perPage;
    $holders = array_slice($allHolders, $offset, $perPage);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Token Holders - is.gudtek.lol</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --crt-green: #0f0;
            --crt-dark: #001a00;
            --crt-glow: rgba(0, 255, 0, 0.3);
        }

        body {
            background: #000;
            color: var(--crt-green);
            font-family: 'Courier New', monospace;
            line-height: 1.6;
            position: relative;
            overflow-x: hidden;
            padding: 2rem;
        }

        body::before {
            content: " ";
            display: block;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.25) 50%), linear-gradient(90deg, rgba(255, 0, 0, 0.06), rgba(0, 255, 0, 0.02), rgba(0, 0, 255, 0.06));
            z-index: 2;
            background-size: 100% 2px, 3px 100%;
            pointer-events: none;
            animation: flicker 0.15s infinite;
        }

        @keyframes flicker {
            0% { opacity: 0.27861; }
            50% { opacity: 0.96019; }
            100% { opacity: 0.24387; }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 3;
        }

        header {
            text-align: center;
            padding: 2rem 0;
            border-bottom: 2px solid var(--crt-green);
            margin-bottom: 2rem;
            text-shadow: 0 0 10px var(--crt-glow);
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            letter-spacing: 2px;
        }

        .info-box {
            border: 1px solid var(--crt-green);
            padding: 1.5rem;
            margin: 2rem 0;
            background: rgba(0, 255, 0, 0.02);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 0.5rem 0;
            padding: 0.5rem;
            border-bottom: 1px dotted var(--crt-green);
        }

        .info-label {
            opacity: 0.7;
        }

        .info-value {
            font-weight: bold;
            word-break: break-all;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 2rem 0;
        }

        th, td {
            border: 1px solid var(--crt-green);
            padding: 1rem;
            text-align: left;
        }

        th {
            background: rgba(0, 255, 0, 0.1);
            text-shadow: 0 0 10px var(--crt-glow);
        }

        tr:hover {
            background: rgba(0, 255, 0, 0.05);
        }

        button, .button {
            background: transparent;
            color: var(--crt-green);
            border: 1px solid var(--crt-green);
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            font-size: 1rem;
            transition: all 0.2s;
            box-shadow: 0 0 5px var(--crt-glow);
            text-decoration: none;
            display: inline-block;
            margin: 0.5rem;
        }

        button:hover, .button:hover {
            background: var(--crt-green);
            color: #000;
            box-shadow: 0 0 15px var(--crt-green);
        }

        .loading {
            text-align: center;
            padding: 3rem;
            font-size: 1.2rem;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .percentage {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .rank {
            font-weight: bold;
            color: var(--crt-green);
            text-shadow: 0 0 10px var(--crt-glow);
        }

        .controls {
            text-align: center;
            margin: 2rem 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>[ TOKEN HOLDERS ]</h1>
            <p>Real-time holder analysis</p>
        </header>

        <?php if (empty($tokenMint)): ?>
            <div class="info-box">
                <p style="text-align: center;">No token configured in admin panel.</p>
            </div>
        <?php else: ?>
            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Token Mint:</span>
                    <span class="info-value"><?= htmlspecialchars($tokenMint) ?></span>
                </div>
                <?php if ($totalSupply > 0): ?>
                    <div class="info-row">
                        <span class="info-label">Total Supply:</span>
                        <span class="info-value"><?= number_format($totalSupply, 2) ?> tokens</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Total Holders:</span>
                        <span class="info-value"><?= number_format($totalHolders) ?> addresses</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Current Page:</span>
                        <span class="info-value">Page <?= $page ?> of <?= ceil($totalHolders / $perPage) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Showing:</span>
                        <span class="info-value"><?= count($holders) ?> holders (<?= (($page - 1) * $perPage) + 1 ?> - <?= min($page * $perPage, $totalHolders) ?>)</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="controls">
                <?php if (!isset($_GET['fetch'])): ?>
                    <a href="?fetch=1" class="button">LOAD HOLDERS</a>
                <?php else: ?>
                    <a href="?fetch=1" class="button">REFRESH DATA</a>
                <?php endif; ?>
                <a href="index.php" class="button">← BACK TO MAIN</a>
            </div>

            <?php if (isset($_GET['fetch'])): ?>
                <?php if ($error): ?>
                    <div class="info-box">
                        <p style="text-align: center; color: #f00;"><?= htmlspecialchars($error) ?></p>
                    </div>
                <?php elseif (empty($holders)): ?>
                    <div class="info-box">
                        <p style="text-align: center;">No holders found. This might mean:</p>
                        <ul style="margin: 1rem 2rem; text-align: left;">
                            <li>The token has no current holders</li>
                            <li>The API request timed out (try refreshing)</li>
                            <li>There was an error fetching data (check error logs)</li>
                        </ul>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Owner Address</th>
                                <th>Balance</th>
                                <th>% of Supply</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $startRank = ($page - 1) * $perPage + 1;
                            foreach ($holders as $index => $holder):
                            ?>
                                <tr>
                                    <td class="rank"><?= $startRank + $index ?></td>
                                    <td><?= htmlspecialchars(substr($holder['owner'], 0, 8) . '...' . substr($holder['owner'], -8)) ?></td>
                                    <td><?= number_format($holder['uiAmount'], 2) ?></td>
                                    <td class="percentage"><?= number_format(($holder['uiAmount'] / $totalSupply) * 100, 2) ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if ($totalHolders > $perPage): ?>
                        <div class="controls" style="margin-top: 2rem;">
                            <?php
                            $totalPages = ceil($totalHolders / $perPage);
                            if ($page > 1): ?>
                                <a href="?fetch=1&page=1" class="button">« FIRST</a>
                                <a href="?fetch=1&page=<?= $page - 1 ?>" class="button">‹ PREV</a>
                            <?php endif; ?>

                            <span style="padding: 0 1rem;">Page <?= $page ?> of <?= $totalPages ?></span>

                            <?php if ($page < $totalPages): ?>
                                <a href="?fetch=1&page=<?= $page + 1 ?>" class="button">NEXT ›</a>
                                <a href="?fetch=1&page=<?= $totalPages ?>" class="button">LAST »</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
