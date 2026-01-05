<?php
// Check if we're on a subdomain and handle redirect
$host = $_SERVER['HTTP_HOST'];
$parts = explode('.', $host);

// Reserved subdomains
$reserved = ['www', 'gudtek', 'mail', 'smtp', 'pop', 'imap', 'ftp', 'admin', 'api', 'dev', 'staging', 'test', 'demo', 'blog', 'shop', 'store', 'cdn', 'static', 'assets', 'images', 'img', 'css', 'js'];

// If subdomain exists (e.g., abc.gudtek.lol or moon.gudtek.lol)
// Expecting format: subdomain.gudtek.lol (3 parts total)
if (count($parts) >= 3) {
    $slug = $parts[0];

    // Skip if it's a reserved subdomain or the main "is" subdomain
    $reserved[] = 'is'; // Add 'is' to reserved to prevent redirect loop
    if (!in_array($slug, $reserved)) {
        // Look up redirect in database
        $db = new SQLite3(__DIR__ . '/data.db');
        $stmt = $db->prepare('SELECT target_url FROM redirects WHERE slug = :slug AND active = 1');
        $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
        $result = $stmt->execute();

        if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            // Check if domain is reserved (no target URL)
            if (empty($row['target_url'])) {
                $db->close();
                // Show reserved domain page
                ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($slug) ?>.gudtek.lol - Reserved</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        :root {
            --crt-green: #0f0;
            --crt-glow: rgba(0, 255, 0, 0.3);
        }
        body {
            background: #000;
            color: var(--crt-green);
            font-family: 'Courier New', monospace;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
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
            text-align: center;
            border: 2px solid var(--crt-green);
            padding: 3rem 2rem;
            max-width: 600px;
            background: rgba(0, 255, 0, 0.02);
            position: relative;
            z-index: 3;
            box-shadow: 0 0 20px var(--crt-glow);
        }
        h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            text-shadow: 0 0 10px var(--crt-glow);
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        p {
            font-size: 1.2rem;
            margin: 1.5rem 0;
            line-height: 1.8;
        }
        .domain {
            color: #fff;
            font-weight: bold;
            text-shadow: 0 0 15px rgba(255, 255, 255, 0.5);
        }
        a {
            display: inline-block;
            margin-top: 2rem;
            padding: 0.75rem 1.5rem;
            border: 1px solid var(--crt-green);
            color: var(--crt-green);
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 0 5px var(--crt-glow);
        }
        a:hover {
            background: var(--crt-green);
            color: #000;
            box-shadow: 0 0 15px var(--crt-green);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>[ RESERVED ]</h1>
        <p>The domain <span class="domain"><?= htmlspecialchars($slug) ?>.gudtek.lol</span> is reserved.</p>
        <p>This domain has been claimed but not yet configured.</p>
        <a href="https://is.gudtek.lol">← Back to is.gudtek.lol</a>
    </div>
</body>
</html>
                <?php
                exit;
            }

            // Has target URL, redirect normally
            header('Location: ' . $row['target_url'], true, 301);
            exit;
        }

        $db->close();
        http_response_code(404);
        die('Redirect not found');
    }
}

// Main page - show the app
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>is.gudtek.lol - Solana URL Shortener</title>
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
        }

        /* CRT effect */
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

        .tek-banner {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, #004400 0%, #00ff00 50%, #004400 100%);
            color: #000;
            text-align: center;
            padding: 0.5rem;
            font-weight: bold;
            font-size: 0.9rem;
            z-index: 999;
            box-shadow: 0 2px 10px rgba(255, 0, 0, 0.5);
        }

        .tek-banner-text {
            animation: textBlink 5s ease-in-out infinite;
        }

        @keyframes textBlink {
            0%, 90%, 100% { opacity: 1; }
            92%, 94%, 96% { opacity: 0.3; }
            93%, 95%, 97% { opacity: 1; }
        }

        body {
            padding-top: 3rem;
        }

        @keyframes flicker {
            0% { opacity: 0.27861; }
            5% { opacity: 0.34769; }
            10% { opacity: 0.23604; }
            15% { opacity: 0.90626; }
            20% { opacity: 0.18128; }
            25% { opacity: 0.83891; }
            30% { opacity: 0.65583; }
            35% { opacity: 0.67807; }
            40% { opacity: 0.26559; }
            45% { opacity: 0.84693; }
            50% { opacity: 0.96019; }
            55% { opacity: 0.08594; }
            60% { opacity: 0.20313; }
            65% { opacity: 0.71988; }
            70% { opacity: 0.53455; }
            75% { opacity: 0.37288; }
            80% { opacity: 0.71428; }
            85% { opacity: 0.70419; }
            90% { opacity: 0.7003; }
            95% { opacity: 0.36108; }
            100% { opacity: 0.24387; }
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 1rem;
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
            font-size: clamp(1.5rem, 5vw, 3rem);
            margin-bottom: 0.5rem;
            letter-spacing: 2px;
            min-height: 3rem;
        }

        .typewriter {
            display: inline-block;
            border-right: 2px solid var(--crt-green);
            animation: blink 0.7s step-end infinite;
            min-width: 20px;
            color: var(--crt-green);
        }

        @keyframes blink {
            50% { border-color: transparent; }
        }

        .tagline {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .availability-checker {
            margin-top: 1.5rem;
            padding: 1rem;
            border: 1px solid var(--crt-green);
            background: rgba(0, 255, 0, 0.02);
            border-radius: 4px;
        }

        .availability-input-wrapper {
            position: relative;
            margin: 0.75rem 0;
        }

        .availability-input {
            width: 100%;
            background: #000;
            color: var(--crt-green);
            border: 1px solid var(--crt-green);
            padding: 0.75rem;
            font-family: 'Courier New', monospace;
            font-size: 1rem;
            box-shadow: inset 0 0 10px var(--crt-glow);
            transition: all 0.3s;
            text-align: center;
            letter-spacing: 2px;
            caret-color: var(--crt-green);
        }

        .availability-input:focus {
            outline: none;
            box-shadow: 0 0 20px var(--crt-green);
            border-color: var(--crt-green);
            animation: inputPulse 1.5s ease-in-out infinite;
        }

        @keyframes inputPulse {
            0%, 100% { box-shadow: 0 0 20px var(--crt-green); }
            50% { box-shadow: 0 0 30px var(--crt-green), inset 0 0 15px var(--crt-glow); }
        }

        .availability-status {
            margin-top: 0.75rem;
            padding: 0.5rem;
            text-align: center;
            font-weight: bold;
            border-radius: 3px;
            transition: all 0.3s;
            min-height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .availability-status.available {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid var(--crt-green);
            color: var(--crt-green);
            animation: glowPulse 1.5s ease-in-out infinite;
        }

        .availability-status.unavailable {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid #f00;
            color: #f00;
        }

        .availability-status.checking {
            background: rgba(255, 255, 0, 0.1);
            border: 1px solid #ff0;
            color: #ff0;
        }

        @keyframes glowPulse {
            0%, 100% { box-shadow: 0 0 5px var(--crt-green); }
            50% { box-shadow: 0 0 20px var(--crt-green); }
        }

        #slugStatus {
            display: block;
            margin-top: 0.5rem;
            font-weight: bold;
            transition: all 0.3s;
            padding: 0.5rem;
            border-radius: 3px;
        }

        #slugStatus.available {
            color: var(--crt-green);
            background: rgba(0, 255, 0, 0.1);
            text-shadow: 0 0 5px var(--crt-green);
        }

        #slugStatus.unavailable {
            color: #f00;
            background: rgba(255, 0, 0, 0.1);
        }

        .wallet-widget {
            position: fixed;
            top: calc(1rem + 50px);
            right: 1rem;
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 50%, #2a2a2a 100%);
            border: 1px solid var(--crt-green);
            padding: 1rem;
            border-radius: 4px;
            box-shadow: 0 0 20px var(--crt-glow);
            z-index: 10000;
            font-size: 0.8rem;
            max-width: 250px;
        }

        .wallet-widget.hidden {
            display: none;
        }

        .wallet-info {
            margin-bottom: 0.5rem;
        }

        .wallet-address {
            word-break: break-all;
            margin-bottom: 0.5rem;
            opacity: 0.9;
        }

        .balance {
            display: flex;
            justify-content: space-between;
            margin: 0.25rem 0;
        }

        button, .admin-link {
            background: transparent;
            color: var(--crt-green);
            border: 1px solid var(--crt-green);
            padding: 0.5rem 1rem;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            transition: all 0.2s;
            box-shadow: 0 0 5px var(--crt-glow);
            width: 100%;
            margin-top: 0.5rem;
            text-decoration: none;
            display: block;
            text-align: center;
        }

        button:hover, .admin-link:hover {
            background: var(--crt-green);
            color: #000;
            box-shadow: 0 0 15px var(--crt-green);
        }

        .hero {
            text-align: center;
            margin: 2rem 0;
            padding: 2rem;
            border: 1px solid var(--crt-green);
            background: rgba(0, 255, 0, 0.02);
        }

        .hero h2 {
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .hero p {
            margin-bottom: 1rem;
            opacity: 0.8;
        }

        #connectBtn {
            max-width: 300px;
            margin: 1rem auto;
        }

        .purchase-section {
            display: none;
            margin: 2rem 0;
            padding: 2rem;
            border: 1px solid var(--crt-green);
            background: rgba(0, 255, 0, 0.02);
        }

        .purchase-section.active {
            display: block;
        }

        input[type="text"] {
            background: #000;
            color: var(--crt-green);
            border: 1px solid var(--crt-green);
            padding: 0.5rem;
            font-family: 'Courier New', monospace;
            font-size: 1rem;
            width: 100%;
            margin: 0.5rem 0;
            box-shadow: inset 0 0 10px var(--crt-glow);
            text-align: center;
            letter-spacing: 2px;
            caret-color: var(--crt-green);
        }

        input[type="text"]:focus {
            outline: none;
            box-shadow: 0 0 15px var(--crt-green);
            animation: inputPulse 1.5s ease-in-out infinite;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
        }

        .price-info {
            text-align: center;
            margin: 1rem 0;
            padding: 1rem;
            background: rgba(0, 255, 0, 0.05);
            border: 1px dashed var(--crt-green);
        }

        .sponsors {
            margin: 2rem 0;
            text-align: center;
        }

        .sponsors h3 {
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .sponsor-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .sponsor-box {
            border: 1px solid var(--crt-green);
            padding: 1rem;
            background: rgba(0, 255, 0, 0.02);
            transition: all 0.2s;
            color: var(--crt-green);
            text-decoration: none;
            display: block;
        }

        .sponsor-box:hover {
            box-shadow: 0 0 20px var(--crt-glow);
            background: rgba(0, 255, 0, 0.05);
        }

        .domains-list {
            margin: 2rem 0;
        }

        .domains-list h3 {
            margin-bottom: 1rem;
            font-size: 1.2rem;
            text-align: center;
        }

        .domain-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem;
            border-bottom: 1px solid rgba(0, 255, 0, 0.2);
            font-size: 0.85rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .domain-item:hover {
            background: rgba(0, 255, 0, 0.05);
        }

        .domain-slug {
            font-weight: bold;
            color: var(--crt-green);
            text-decoration: none;
            transition: all 0.2s;
        }

        .domain-slug:hover {
            text-shadow: 0 0 10px var(--crt-glow);
            text-decoration: underline;
        }

        .domain-url {
            opacity: 0.7;
            word-break: break-all;
        }

        .btn-delete {
            background: transparent;
            color: #f00;
            border: 1px solid #f00;
            padding: 0.25rem 0.5rem;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            font-size: 1rem;
            transition: all 0.2s;
            margin-left: auto;
        }

        .btn-delete:hover {
            background: #f00;
            color: #000;
            box-shadow: 0 0 15px rgba(255, 0, 0, 0.5);
        }

        .status-msg {
            padding: 1rem;
            margin: 1rem 0;
            border: 1px solid var(--crt-green);
            text-align: center;
            display: none;
        }

        .status-msg.error {
            border-color: #f00;
            color: #f00;
            text-shadow: 0 0 10px rgba(255, 0, 0, 0.5);
        }

        .status-msg.success {
            display: block;
        }

        @media (max-width: 768px) {
            .wallet-widget {
                position: static;
                margin: 1rem auto;
                max-width: 100%;
            }

            .container {
                padding: 0.5rem;
            }
        }

        /* Custom Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            animation: fadeIn 0.2s;
        }

        .modal-overlay.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-box {
            background: #000;
            border: 2px solid var(--crt-green);
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 0 30px var(--crt-glow), inset 0 0 20px rgba(0, 255, 0, 0.1);
            position: relative;
            animation: modalSlide 0.3s;
        }

        .modal-box::-webkit-scrollbar {
            width: 10px;
        }

        .modal-box::-webkit-scrollbar-track {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid var(--crt-green);
        }

        .modal-box::-webkit-scrollbar-thumb {
            background: var(--crt-green);
            box-shadow: 0 0 5px var(--crt-glow);
        }

        .modal-box::-webkit-scrollbar-thumb:hover {
            background: #0f0;
            box-shadow: 0 0 10px var(--crt-glow);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes modalSlide {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-title {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            text-shadow: 0 0 10px var(--crt-glow);
            border-bottom: 1px solid var(--crt-green);
            padding-bottom: 0.5rem;
        }

        .modal-message {
            margin: 1.5rem 0;
            line-height: 1.8;
        }

        .modal-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .modal-buttons button {
            flex: 1;
        }

        .modal-link {
            color: var(--crt-green);
            text-decoration: underline;
            cursor: pointer;
            word-break: break-all;
        }

        .modal-link:hover {
            text-shadow: 0 0 10px var(--crt-glow);
        }
    </style>
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
    <!-- Tek Loading Banner -->
    <div class="tek-banner">
        <span class="tek-banner-text">🚀 tek loading... domain marketplace shipping soon 🚀</span>
    </div>

    <!-- Custom Modal -->
    <div id="modalOverlay" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-title" id="modalTitle">[ SYSTEM MESSAGE ]</div>
            <div class="modal-message" id="modalMessage"></div>
            <div class="modal-buttons" id="modalButtons"></div>
        </div>
    </div>
    <div class="container">
        <header>
            <h1>[ <span id="typewriter" class="typewriter"></span>.gudtek.lol ]</h1>
            <p class="tagline">Decentralized URL Shortener on Solana</p>
        </header>

        <div id="walletWidget" class="wallet-widget hidden">
            <div class="wallet-info">
                <div class="wallet-address" id="walletAddress"></div>
                <div class="balance">
                    <span>SOL:</span>
                    <span id="solBalance">...</span>
                </div>
                <div class="balance">
                    <span>TOKEN:</span>
                    <span id="tokenBalance">...</span>
                </div>
            </div>
            <a href="admin.php" id="adminLink" class="admin-link" style="display: none;">ADMIN PANEL</a>
            <a href="holders.php" class="admin-link">VIEW HOLDERS</a>
            <button id="disconnectBtn">DISCONNECT</button>
        </div>

        <div class="hero">
            <h2>&gt; Own Your .gudtek.lol "Domain"</h2>

            <!-- Availability Checker (shown when not connected) -->
            <div id="heroAvailabilityChecker" class="availability-checker" style="display: none;">
                <p style="opacity: 0.9; margin-bottom: 0.75rem; font-size: 0.95rem;">✨ Check Domain Availability</p>
                <div class="availability-input-wrapper">
                    <input type="text" id="heroSlugInput" class="availability-input" placeholder="Enter your desired domain (e.g., moon)" maxlength="50">
                </div>
                <div id="heroAvailabilityStatus" class="availability-status"></div>
            </div>

            <p style="margin-top: 1.5rem;">Create your custom .gudtek.lol domain on Solana blockchain.</p>
            <p>Pay with SOL or tokens. Point it anywhere you want. Forever yours<span onclick="showHowItWorksModal()" style="cursor: pointer; color: #f00; font-weight: bold;">*</span></p>
        <button onclick="showHowItWorksModal()" style="margin-top: 1rem; max-width: 300px; margin-left: auto; margin-right: auto;">HOW DOES IT WORK?</button>
            <div id="tokenContract" style="margin-top: 1.5rem; padding: 1rem; border: 1px dashed var(--crt-green); background: rgba(0, 255, 0, 0.02); font-size: 0.85rem; text-align: center;">
                <p style="opacity: 0.8; margin-bottom: 0.5rem;">Ecosystem Utility Token:</p>
                <p id="tokenMintDisplay" style="font-family: monospace; word-break: break-all; color: var(--crt-green);">Loading...</p>
            </div>
            <button id="connectBtn">CONNECT PHANTOM WALLET</button>
        </div>

        <div class="sponsors">
            <h3>&gt; Last Domains</h3>
            <div class="sponsor-grid">
                <a href="https://moon.gudtek.lol" target="_blank" class="sponsor-box">moon</a>
                <a href="https://pump.gudtek.lol" target="_blank" class="sponsor-box">pump</a>
                <a href="https://wagmi.gudtek.lol" target="_blank" class="sponsor-box">wagmi</a>
                <a href="https://gm.gudtek.lol" target="_blank" class="sponsor-box">gm</a>
            </div>
        </div>

        <div id="purchaseSection" class="purchase-section">
            <h2>&gt; Purchase your .gudtek.lol domain</h2>
            <div class="price-info" id="priceInfo">
                Loading prices...
            </div>

            <div class="form-group">
                <label for="slugInput">Choose Your Domain Name (e.g., "moon"):</label>
                <input type="text" id="slugInput" placeholder="Enter your domain name" maxlength="50">
                <small id="slugStatus"></small>
            </div>

            <div class="form-group">
                <label for="urlInput">Where Should It Point? (URL):</label>
                <input type="text" id="urlInput" placeholder="https://example.com">
            </div>

            <div class="form-group">
                <label>Payment Method:</label>
                <button id="paySolBtn">PAY WITH SOL</button>
                <button id="payTokenBtn">PAY WITH TOKEN</button>
                <button id="payStripeBtn" style="background: linear-gradient(135deg, #635bff, #5469d4); border: 2px solid #635bff;">PAY WITH CARD 💳</button>
            </div>

            <div id="statusMsg" class="status-msg"></div>
        </div>

        <div id="myDomainsSection" class="purchase-section">
            <h2>&gt; Your Domains</h2>
            <div id="myDomainsList"></div>
        </div>

        <div class="domains-list">
            <h3>&gt; Recently Created Domains</h3>
            <div id="domainsList"></div>
        </div>

        <div class="sponsors">
            <h3>&gt; Sponsors</h3>
            <div class="sponsor-grid">
                <div class="sponsor-box">SPONSOR SLOT 1</div>
                <div class="sponsor-box">SPONSOR SLOT 2</div>
                <div class="sponsor-box">SPONSOR SLOT 3</div>
                <div class="sponsor-box">SPONSOR SLOT 4</div>
            </div>
        </div>

        <div class="section" style="margin-top: 3rem;">
            <h3>&gt; Feature Requests & Contact</h3>
            <p style="margin-bottom: 1rem; opacity: 0.9;">Have an idea or want to get in touch? Send us a message!</p>
            <div class="form-group">
                <textarea id="contactMessage" placeholder="Your message, feature request, or feedback..." rows="4" style="width: 100%; background: #000; color: var(--crt-green); border: 1px solid var(--crt-green); padding: 0.75rem; font-family: 'Courier New', monospace; font-size: 1rem; resize: vertical; box-shadow: inset 0 0 10px var(--crt-glow);"></textarea>
            </div>
            <button id="sendMessageBtn" style="margin-top: 0.5rem;">SEND MESSAGE</button>
            <div id="messageStatus" style="margin-top: 1rem; padding: 0.75rem; border: 1px solid var(--crt-green); display: none;"></div>
        </div>

        <div style="border-top: 1px solid var(--crt-green); margin-top: 3rem; padding-top: 2rem; opacity: 0.7; font-size: 0.85rem; text-align: center;">
            <p><strong>DISCLAIMER:</strong> We do not endorse or take responsibility for the content of external links. Users are responsible for the content they link to.</p>
            <p style="margin-top: 1rem;">We will do everything possible to keep this service alive, but we take no warranty or responsibility for service availability, uptime, or data persistence. Use at your own risk.</p>
            <p style="margin-top: 1rem;">Found problematic content? Report domain: <a href="mailto:report@gudtek.lol" style="color: var(--crt-green); text-decoration: underline;">report@gudtek.lol</a></p>
        </div>

        <div style="margin-top: 2rem; padding: 1.5rem 0; text-align: center; opacity: 0.8; font-size: 0.9rem; border-top: 1px solid rgba(0, 255, 0, 0.2);">
            <p style="display: flex; align-items: center; justify-content: center; gap: 1rem; flex-wrap: wrap;">
                <span>Built with ❤️ and green phosphor</span>
                <a href="https://github.com/isgudtek/dns-gudtek.lol" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 0.4rem; color: var(--crt-green); text-decoration: none; transition: all 0.3s ease;" onmouseover="this.style.textShadow='0 0 15px var(--crt-glow)'; this.style.transform='scale(1.05)'" onmouseout="this.style.textShadow=''; this.style.transform='scale(1)'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0 0 5px var(--crt-glow));">
                        <path d="M12 2C6.477 2 2 6.477 2 12c0 4.42 2.865 8.17 6.839 9.49.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.603-3.369-1.34-3.369-1.34-.454-1.156-1.11-1.463-1.11-1.463-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.831.092-.646.35-1.086.636-1.336-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.578 9.578 0 0112 6.836c.85.004 1.705.114 2.504.336 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.578.688.48C19.138 20.167 22 16.418 22 12c0-5.523-4.477-10-10-10z" fill="#fff" fill-opacity="0.9"/>
                    </svg>
                    <span>GitHub</span>
                </a>
            </p>
        </div>
    </div>

    <!-- Use UMD build with all polyfills included -->
    <script src="https://unpkg.com/@solana/web3.js@latest/lib/index.iife.js"></script>
    <script src="app.js"></script>
</body>
</html>
