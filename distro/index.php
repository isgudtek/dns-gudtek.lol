<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Distribution Flywheel - is.gudtek.lol</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --neon-green: #00ff41;
            --neon-cyan: #00ffff;
            --neon-pink: #ff10f0;
            --dark-bg: #000000;
            --card-bg: rgba(0, 26, 0, 0.6);
        }

        @keyframes matrix-fall {
            0% { transform: translateY(-100%); }
            100% { transform: translateY(100vh); }
        }

        @keyframes pulse-glow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.9; }
        }

        @keyframes scan-line {
            0% { transform: translateY(-100%); }
            100% { transform: translateY(100%); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }

        @keyframes flicker {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        @keyframes border-flow {
            0% { border-image-source: linear-gradient(90deg, var(--neon-green), var(--neon-cyan)); }
            50% { border-image-source: linear-gradient(90deg, var(--neon-cyan), var(--neon-pink)); }
            100% { border-image-source: linear-gradient(90deg, var(--neon-green), var(--neon-cyan)); }
        }

        body {
            background: #000;
            color: var(--neon-green);
            font-family: 'Courier New', monospace;
            line-height: 1.6;
            position: relative;
            overflow-x: hidden;
            min-height: 100vh;
        }

        /* Matrix Background */
        .matrix-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            opacity: 0.15;
            pointer-events: none;
        }

        .matrix-column {
            position: absolute;
            top: -100%;
            font-size: 20px;
            color: var(--neon-green);
            text-shadow: 0 0 8px var(--neon-green);
            animation: matrix-fall linear infinite;
        }

        /* Scan Line Effect */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(transparent, var(--neon-green), transparent);
            z-index: 9999;
            animation: scan-line 8s linear infinite;
            pointer-events: none;
            opacity: 0.5;
        }

        /* CRT Effect */
        body::after {
            content: " ";
            display: block;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.25) 50%);
            z-index: 2;
            background-size: 100% 2px;
            pointer-events: none;
            animation: flicker 0.15s infinite;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 2rem;
            position: relative;
            z-index: 3;
        }

        header {
            text-align: center;
            padding: 3rem 0;
            position: relative;
            margin-bottom: 3rem;
        }

        h1 {
            font-size: 4rem;
            text-shadow: var(--glow-strong);
            margin-bottom: 1rem;
            letter-spacing: 8px;
            animation: pulse-glow 3s ease-in-out infinite;
            background: linear-gradient(90deg, var(--neon-green), var(--neon-cyan), var(--neon-green));
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 3s linear infinite, float 4s ease-in-out infinite;
        }

        .subtitle {
            font-size: 1.3rem;
            color: var(--neon-cyan);
            text-shadow: 0 0 10px var(--neon-cyan);
            letter-spacing: 3px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 2px solid var(--neon-green);
            padding: 2rem;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .card::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 255, 65, 0.2), transparent);
            animation: card-glare 8s linear infinite;
            pointer-events: none;
        }

        @keyframes card-glare {
            0% { left: -100%; }
            50% { left: 100%; }
            100% { left: 100%; }
        }

        .card:hover {
            transform: translateY(-5px);
            border-color: var(--neon-cyan);
        }

        .card h2 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            text-shadow: 0 0 15px var(--neon-green);
            border-bottom: 2px solid var(--neon-green);
            padding-bottom: 1rem;
            letter-spacing: 2px;
            position: relative;
        }

        .card h2::after {
            content: "█";
            position: absolute;
            right: 0;
            animation: flicker 1s infinite;
        }

        .stat {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(0, 255, 65, 0.2);
            transition: all 0.3s;
        }

        .stat:hover {
            background: rgba(0, 255, 65, 0.1);
            padding-left: 1rem;
            padding-right: 1rem;
            margin-left: -1rem;
            margin-right: -1rem;
        }

        .stat:last-child {
            border-bottom: none;
        }

        .stat-label {
            opacity: 0.8;
            font-size: 1.1rem;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            text-shadow: 0 0 10px var(--neon-green);
            color: var(--neon-cyan);
        }

        .countdown-card {
            grid-column: 1 / -1;
            background: linear-gradient(135deg, rgba(0, 26, 0, 0.8) 0%, rgba(0, 0, 26, 0.8) 100%);
            border: 3px solid var(--neon-green);
            animation: pulse-glow 2s ease-in-out infinite;
        }

        /* Floating boxes layout - Desktop only */
        @media (min-width: 1200px) {
            .countdown-main-content {
                display: flex;
                align-items: flex-start;
                gap: 2rem;
                justify-content: space-between;
            }

            .floating-info-box {
                flex: 0 0 340px;
                margin: 0 !important;
                background: #000000 !important;
                border: 3px solid var(--neon-cyan);
                padding: 1.5rem !important;
                position: relative;
                z-index: 10;
            }

            .floating-info-box::before {
                display: none !important;
            }

            .floating-info-box h2 {
                font-size: 1.1rem;
                margin-bottom: 1.2rem;
            }

            .floating-info-box .stat {
                margin-bottom: 0.8rem;
            }

            .floating-info-box .stat-label {
                font-size: 0.85rem;
            }

            .floating-info-box .stat-value {
                font-size: 1.3rem;
            }

            .floating-info-box button {
                font-size: 0.85rem !important;
                padding: 0.8rem 1.2rem !important;
            }

            .sol-icon {
                height: 18px;
                width: auto;
                vertical-align: middle;
                margin-left: 6px;
                margin-top: -5px;
                display: inline-block;
            }

            .countdown-center {
                flex: 1;
                max-width: 600px;
                margin: 0 auto;
            }
        }

        /* Mobile: Stack vertically */
        @media (max-width: 1199px) {
            .countdown-main-content {
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
            }

            .floating-info-box {
                width: 100%;
            }

            .countdown-center {
                width: 100%;
            }
        }

        .countdown {
            font-size: 5rem;
            text-align: center;
            padding: 2rem;
            text-shadow: var(--glow-strong);
            background: linear-gradient(90deg, var(--neon-green), var(--neon-cyan), var(--neon-pink), var(--neon-green));
            background-size: 300% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 5s linear infinite;
            font-weight: bold;
            letter-spacing: 10px;
        }

        .progress-bar {
            position: relative;
            height: 80px;
            border: 3px solid var(--neon-green);
            margin: 2rem 0;
            background: rgba(0, 0, 0, 0.8);
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg,
                var(--neon-green) 0%,
                var(--neon-cyan) 50%,
                var(--neon-pink) 100%);
            transition: width 1s ease;
            position: relative;
            overflow: hidden;
        }

        .progress-fill::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            animation: shimmer 2s linear infinite;
        }

        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #fff;
            font-weight: bold;
            font-size: 2.5rem;
            text-shadow: 0 0 20px #000, 0 0 40px var(--neon-green);
            letter-spacing: 3px;
        }

        .progress-subtitle {
            text-align: center;
            opacity: 0.9;
            font-size: 1.1rem;
            color: var(--neon-cyan);
            text-shadow: 0 0 10px var(--neon-cyan);
            letter-spacing: 2px;
        }

        button {
            background: rgba(0, 0, 0, 0.8);
            color: var(--neon-green);
            border: 2px solid var(--neon-green);
            padding: 1rem 2rem;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            font-weight: bold;
            transition: all 0.2s;
            margin: 0.5rem 0.5rem 0.5rem 0;
            width: 100%;
            letter-spacing: 2px;
            position: relative;
            z-index: 100;
            pointer-events: auto;
        }

        button:hover {
            background: rgba(0, 255, 65, 0.1);
            border-color: var(--neon-cyan);
        }

        button:active {
            transform: scale(0.98);
        }

        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .btn-danger {
            border-color: var(--neon-pink);
            color: var(--neon-pink);
        }

        .btn-danger::before {
            background: var(--neon-pink);
        }

        input[type="text"],
        input[type="number"] {
            background: rgba(0, 0, 0, 0.8);
            color: var(--neon-green);
            border: 2px solid var(--neon-green);
            padding: 1rem;
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            width: 100%;
            margin: 0.5rem 0;
            transition: all 0.3s;
            position: relative;
            z-index: 100;
            pointer-events: auto;
        }

        input:focus {
            outline: none;
            border-color: var(--neon-cyan);
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--neon-cyan);
            font-size: 1.1rem;
            text-shadow: 0 0 5px var(--neon-cyan);
        }

        small {
            opacity: 0.7;
            display: block;
            margin-top: 0.5rem;
            color: var(--neon-cyan);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            border: 1px solid var(--neon-green);
            padding: 1rem;
            text-align: left;
            transition: all 0.3s;
        }

        th {
            background: rgba(0, 255, 65, 0.15);
            text-shadow: 0 0 10px var(--neon-green);
            font-size: 1.1rem;
            letter-spacing: 1px;
        }

        tr:hover {
            background: rgba(0, 255, 65, 0.1);
        }

        .section {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 2px solid var(--neon-green);
            padding: 2rem;
            margin: 2rem 0;
            position: relative;
            overflow: hidden;
        }

        .section::after {
            content: "";
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(transparent, rgba(0, 255, 65, 0.05), transparent);
            transform: rotate(45deg);
            animation: shimmer 10s linear infinite;
        }

        .status {
            padding: 1.5rem;
            margin: 1rem 0;
            border: 2px solid var(--neon-green);
            display: none;
            font-size: 1.1rem;
        }

        .status.success {
            background: rgba(0, 255, 65, 0.2);
            border-color: var(--neon-green);
            animation: pulse-glow 1s ease-in-out 3;
        }

        .status.error {
            background: rgba(255, 16, 240, 0.2);
            border-color: var(--neon-pink);
            color: var(--neon-pink);
        }

        .wallet-widget {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, rgba(0, 26, 0, 0.95) 0%, rgba(0, 0, 26, 0.95) 100%);
            backdrop-filter: blur(10px);
            border: 2px solid var(--neon-green);
            padding: 1.5rem;
            z-index: 10000;
            max-width: 350px;
            animation: float 4s ease-in-out infinite;
        }

        .wallet-widget.hidden {
            display: none;
        }

        @media (max-width: 768px) {
            .wallet-widget {
                position: relative;
                top: 0;
                right: 0;
                margin-bottom: 1rem;
                max-width: 100%;
            }

            h1 {
                font-size: 2.5rem;
            }

            .countdown {
                font-size: 3rem;
            }

            .grid {
                grid-template-columns: 1fr;
            }
        }

        /* Loading Animation */
        @keyframes dots {
            0%, 20% { content: '.'; }
            40% { content: '..'; }
            60%, 100% { content: '...'; }
        }

        .loading::after {
            content: '.';
            animation: dots 1.5s infinite;
        }

        /* Glitch Effect */
        .card:hover h2 {
            /* No animation - keep it clean */
        }

        /* Responsive Design for Mobile */
        @media (max-width: 768px) {
            body {
                font-size: 14px;
                zoom: 0.9;
            }

            .container {
                padding: 1rem;
                max-width: 100%;
            }

            .grid {
                grid-template-columns: 1fr !important;
                gap: 1rem;
            }

            .countdown {
                font-size: 2.5rem !important;
                padding: 1rem !important;
                letter-spacing: 2px !important;
            }

            .progress-bar {
                height: 50px !important;
            }

            h1 {
                font-size: 1.8rem !important;
            }

            h2 {
                font-size: 1.3rem !important;
            }

            .card {
                padding: 1.5rem !important;
            }

            button {
                padding: 1rem 2rem !important;
                font-size: 0.9rem !important;
            }

            input[type="text"],
            input[type="number"] {
                font-size: 14px;
                padding: 0.8rem;
            }

            table {
                font-size: 0.85rem;
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }

            .stat {
                flex-direction: column;
                gap: 0.3rem;
            }

            .stat-label,
            .stat-value {
                font-size: 1rem;
            }

            #activityLog {
                font-size: 0.8rem !important;
                max-height: 250px !important;
            }

            img#timerGif {
                max-width: 180px !important;
            }
        }

        @media (max-width: 480px) {
            body {
                zoom: 0.85;
            }

            .countdown {
                font-size: 2rem !important;
                padding: 0.5rem !important;
            }

            .container {
                padding: 0.5rem;
            }

            button {
                padding: 0.8rem 1.5rem !important;
                font-size: 0.85rem !important;
            }

            h1 {
                font-size: 1.5rem !important;
            }

            img#timerGif {
                max-width: 150px !important;
            }
        }
    </style>
</head>
<body>
    <!-- Matrix Background -->
    <div class="matrix-bg" id="matrixBg"></div>

    <div class="container">
        <div id="walletWidget" class="wallet-widget hidden">
            <div style="margin-bottom: 0.5rem; font-size: 1.2rem; color: var(--neon-cyan);"><strong>█ ADMIN CONNECTED</strong></div>
            <div style="font-size: 0.9rem; opacity: 0.9; word-break: break-all; color: var(--neon-green);" id="walletAddress"></div>
            <button id="disconnectBtn" style="margin-top: 1rem; font-size: 0.9rem;"><span>DISCONNECT</span></button>
        </div>

        <div class="card countdown-card" style="margin-bottom: 2rem;">
            <h2>» AUTOMATED TREASURY DISTRIBUTION SYSTEM</h2>

            <!-- Main content with floating boxes on large screens -->
            <div class="countdown-main-content">
                <!-- Royalty Info - floats left on desktop -->
                <div class="card floating-info-box floating-left">
                    <h2>» ROYALTIES</h2>
                    <div class="stat">
                        <span class="stat-label">Last Claimed:</span>
                        <span class="stat-value" id="lastClaimed">--.----<img src="https://uxwing.com/wp-content/themes/uxwing/download/brands-and-social-media/solana-sol-icon.png" class="sol-icon" alt="SOL"></span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">Total Collected:</span>
                        <span class="stat-value" id="totalCollected">--.----<img src="https://uxwing.com/wp-content/themes/uxwing/download/brands-and-social-media/solana-sol-icon.png" class="sol-icon" alt="SOL"></span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">Next Drop:</span>
                        <span class="stat-value" id="nextDrop">--.----<img src="https://uxwing.com/wp-content/themes/uxwing/download/brands-and-social-media/solana-sol-icon.png" class="sol-icon" alt="SOL"></span>
                    </div>
                </div>

                <!-- Center GIF -->
                <div class="countdown-center">
                    <p class="progress-subtitle" id="tokenAddress" style="margin-top: 0; margin-bottom: 1rem; font-size: 0.85rem; cursor: pointer; transition: opacity 0.2s;" onclick="copyTokenAddress()" title="Click to copy">[ LOADING... ]</p>
                    <div style="text-align: center; margin: 2rem 0;">
                        <img id="timerGif" src="https://cdn.cdnstep.com/uTxnRNiLe6APTZEPZilx/15.thumb256.webp" alt="Timer Animation" style="max-width: 256px; border-radius: 10px; animation: pulse-glow 2s ease-in-out infinite;">
                    </div>
                    <div class="countdown" id="countdown">--:--:--</div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="progressFill" style="width: 0%"></div>
                        <div class="progress-text" id="progressText">0%</div>
                    </div>
                    <p class="progress-subtitle">[ NEXT AUTOMATIC DISTRIBUTION ]</p>
                </div>

                <!-- Token Holders - floats right on desktop -->
                <div class="card floating-info-box floating-right">
                    <!-- Token Info -->
                    <div id="tokenInfo" style="text-align: center; margin-bottom: 1rem; display: none;">
                        <img id="tokenImage" src="" alt="Token" style="width: 60px; height: 60px; border-radius: 50%; margin-bottom: 0.5rem;">
                        <div id="tokenName" style="font-size: 1rem; font-weight: bold; color: var(--neon-green); margin-bottom: 0.2rem;">---</div>
                        <div id="tokenTicker" style="font-size: 0.8rem; color: var(--neon-cyan); opacity: 0.8;">---</div>
                    </div>

                    <h2>» HOLDERS</h2>
                    <div class="stat">
                        <span class="stat-label">Total:</span>
                        <span class="stat-value" id="totalHolders">--</span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">Supply:</span>
                        <span class="stat-value" id="totalSupply">--</span>
                    </div>
                    <button id="connectBtn"><span>CONNECT WALLET</span></button>
                    <button id="executeBtn" style="display: none;" class="btn-danger"><span>EXECUTE NOW</span></button>
                    <div id="executeStatus" class="status"></div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2 style="border-bottom: 2px solid var(--neon-green); padding-bottom: 1rem; margin-bottom: 2rem; font-size: 1.8rem;">» SYSTEM ACTIVITY LOG</h2>
            <div id="activityLog" style="background: rgba(0, 0, 0, 0.8); border: 2px solid var(--neon-green); padding: 1.5rem; max-height: 400px; overflow-y: auto; font-size: 0.95rem;">
                <div class="log-entry" style="opacity: 0.7; margin-bottom: 0.5rem;">
                    <span style="color: var(--neon-cyan);">[SYSTEM]</span> Initializing distribution flywheel...
                </div>
            </div>
        </div>

        <div class="section" id="adminConfig" style="display: none;">
            <h2 style="border-bottom: 2px solid var(--neon-green); padding-bottom: 1rem; margin-bottom: 2rem; font-size: 1.8rem; text-shadow: var(--glow);">» ADMIN CONFIGURATION</h2>
            <div style="display: grid; grid-template-columns: 1fr; gap: 2rem; margin-bottom: 2rem;">
                <div>
                    <label>▸ Token Mint Address:</label>
                    <input type="text" id="configTokenMint" placeholder="Enter Solana token mint address">
                    <small>The SPL token whose holders will receive distributions</small>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                <div>
                    <label>▸ Distribution Interval (seconds):</label>
                    <input type="number" id="configInterval" placeholder="86400">
                    <small>86400 = 24 hours, 3600 = 1 hour, 60 = 1 minute, 10 = 10 seconds</small>
                </div>
                <div>
                    <label>▸ Distribution Percentage:</label>
                    <input type="number" id="configPercentage" min="1" max="100" placeholder="50">
                    <small>1-100% of treasury balance to distribute</small>
                </div>
                <div>
                    <label>
                        <input type="checkbox" id="configEnabled" style="margin-right: 0.5rem; width: auto;">
                        Enable Automatic Distributions
                    </label>
                </div>
            </div>

            <div style="margin-top: 2rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <button id="startTimerBtn" style="width: auto; padding: 1rem 2rem;">START TIMER</button>
                <button id="stopTimerBtn" style="width: auto; padding: 1rem 2rem;">STOP TIMER</button>
                <button id="saveConfigBtn" style="width: auto; padding: 1.5rem 3rem;">SAVE CONFIGURATION</button>
                <span id="timerStatus" style="color: var(--neon-cyan); font-size: 1.1rem; font-weight: bold; margin-left: 1rem;"></span>
                <span id="daemonStatus" style="color: var(--neon-cyan); font-size: 1.1rem; font-weight: bold; margin-left: 1rem;">■ DAEMON STOPPED</span>
            </div>
            <div id="configStatus" class="status"></div>
        </div>

        <div class="section">
            <h2 style="border-bottom: 2px solid var(--neon-green); padding-bottom: 1rem; margin-bottom: 2rem; font-size: 1.8rem; text-shadow: var(--glow);">» DISTRIBUTION HISTORY</h2>
            <div id="historyList">
                <p class="loading">Loading</p>
            </div>
        </div>

        <div class="section">
            <h2 style="border-bottom: 2px solid var(--neon-green); padding-bottom: 1rem; margin-bottom: 2rem; font-size: 1.8rem; text-shadow: var(--glow);">» TOP TOKEN HOLDERS</h2>
            <div id="holdersList">
                <p class="loading">Loading</p>
            </div>
        </div>
    </div>

    <!-- Matrix Rain Effect -->
    <script>
        // Create matrix rain effect
        function createMatrixRain() {
            const bg = document.getElementById('matrixBg');
            const chars = '01アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲン';
            const columns = Math.floor(window.innerWidth / 20);

            for (let i = 0; i < columns; i++) {
                const column = document.createElement('div');
                column.className = 'matrix-column';
                column.style.left = (i * 20) + 'px';
                column.style.animationDuration = (Math.random() * 3 + 2) + 's';
                column.style.animationDelay = (Math.random() * 5) + 's';

                let text = '';
                for (let j = 0; j < 30; j++) {
                    text += chars[Math.floor(Math.random() * chars.length)] + '<br>';
                }
                column.innerHTML = text;

                bg.appendChild(column);
            }
        }

        createMatrixRain();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/@solana/web3.js@1.87.6/lib/index.iife.min.js"></script>
    <script src="app.js?v=7.6-FIXED"></script>
</body>
</html>
