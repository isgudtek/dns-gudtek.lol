// Phantom Wallet Integration
let provider = null;
let publicKey = null;
const ADMIN_WALLETS = [
    'DmXxdBDNxe7Rrq1SReV6FQcG1cswrL2MHxYQPf4J9XdS',
    '5Mb8vcPzw2CgEub9NbTUJPQCnoLTRaGiamTkjvETLYzC',
    '819ywRTzmw3Gfei4UgBbmw3FaNRVaPu8Npmz4bcRZFA6'
];

// State
let status = null;
let holders = null;
let daemonStatus = { running: false };
let displayedDaemonLogs = new Set(); // Track which daemon logs we've already shown
let currentTokenMint = null; // Track current token to detect changes

// Activity Log
function addLog(message, type = 'info') {
    const log = document.getElementById('activityLog');
    if (!log) return;

    const timestamp = new Date().toLocaleTimeString();
    const colors = {
        info: 'var(--neon-cyan)',
        success: 'var(--neon-green)',
        error: 'var(--neon-pink)',
        warning: '#ffaa00'
    };

    const entry = document.createElement('div');
    entry.style.marginBottom = '0.5rem';
    entry.innerHTML = `<span style="color: ${colors[type]};">[${timestamp}]</span> ${message}`;
    log.appendChild(entry);
    log.scrollTop = log.scrollHeight;

    // Keep last 100
    const entries = log.children;
    if (entries.length > 100) {
        log.removeChild(entries[0]);
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    if ('phantom' in window) {
        const phantomProvider = window.phantom?.solana;
        if (phantomProvider?.isPhantom) {
            provider = phantomProvider;
        }
    }

    document.getElementById('connectBtn').addEventListener('click', connectWallet);
    document.getElementById('disconnectBtn').addEventListener('click', disconnectWallet);
    document.getElementById('executeBtn').addEventListener('click', executeDistribution);
    document.getElementById('saveConfigBtn').addEventListener('click', saveConfiguration);
    document.getElementById('startTimerBtn').addEventListener('click', startTimer);
    document.getElementById('stopTimerBtn').addEventListener('click', stopTimer);

    addLog('System initialized', 'success');
    loadData();
    setInterval(() => loadData(true), 1000); // Update every 1 second for smooth display
});

async function loadData(silent = false) {
    await loadStatus(silent);
    await loadHolders(silent);
    await loadHistory();
    await loadDaemonStatus();
    await loadDaemonActivity();
    await loadRoyaltyStats();
}

async function loadStatus(silent = false) {
    try {
        const response = await fetch('api.php?action=getStatus');
        status = await response.json();

        // Update token address display (without "TOKEN:" label)
        if (status.token_mint) {
            document.getElementById('tokenAddress').textContent = `[ ${status.token_mint} ]`;

            // Load token metadata only when token changes (one-time fetch)
            if (currentTokenMint !== status.token_mint) {
                currentTokenMint = status.token_mint;
                loadTokenMetadata();
            }
        }

        if (!silent) {
            addLog(`Treasury: ${status.treasury_balance.toFixed(4)} SOL`, 'info');
        }

        updateCountdown();
        updateTimerButtons();
    } catch (error) {
        console.error('Failed to load status:', error);
    }
}

// Copy token address to clipboard
function copyTokenAddress() {
    if (!status || !status.token_mint) {
        addLog('No token address available', 'error');
        return;
    }

    navigator.clipboard.writeText(status.token_mint).then(() => {
        // Visual feedback
        const el = document.getElementById('tokenAddress');
        const originalText = el.textContent;
        el.textContent = '[ ✓ COPIED! ]';
        el.style.color = 'var(--neon-green)';

        setTimeout(() => {
            el.textContent = originalText;
            el.style.color = '';
        }, 1500);

        // Log notification
        addLog(`Token address copied: ${status.token_mint}`, 'success');
    }).catch(err => {
        addLog('Failed to copy address', 'error');
    });
}

let lastTimeLeft = -1;
let distributionInProgress = false;

function updateCountdown() {
    if (!status) return;

    const countdownEl = document.getElementById('countdown');
    const progressFillEl = document.getElementById('progressFill');
    const progressTextEl = document.getElementById('progressText');

    if (!countdownEl || !progressFillEl || !progressTextEl) return;

    // Get time remaining from server
    let timeLeft = status.time_until_next || 0;

    // Format time
    const days = Math.floor(timeLeft / 86400);
    const hours = Math.floor((timeLeft % 86400) / 3600);
    const minutes = Math.floor((timeLeft % 3600) / 60);
    const seconds = Math.floor(timeLeft % 60);

    let parts = [];
    if (days > 0) parts.push(`${days}d`);
    if (hours > 0 || days > 0) parts.push(`${hours}h`);
    if (minutes > 0 || hours > 0 || days > 0) parts.push(`${minutes}m`);
    parts.push(`${seconds}s`);

    countdownEl.textContent = parts.join(' ');
    countdownEl.style.opacity = status.timer_running ? '1' : '0.5';

    // Progress bar
    const totalInterval = status.interval_seconds || 60;
    const elapsed = totalInterval - timeLeft;
    const progress = Math.max(0, Math.min(100, (elapsed / totalInterval) * 100));
    progressFillEl.style.width = progress + '%';
    progressTextEl.textContent = Math.floor(progress) + '%';

    // Auto-execute distribution when countdown reaches 0
    if (timeLeft === 0 && lastTimeLeft > 0 && status.timer_running && status.enabled && !distributionInProgress) {
        addLog('Countdown reached 0 - triggering automatic distribution', 'info');
        autoDistribute();
    }

    lastTimeLeft = timeLeft;
}

function updateTimerButtons() {
    if (!status) return;

    const startBtn = document.getElementById('startTimerBtn');
    const stopBtn = document.getElementById('stopTimerBtn');
    const statusEl = document.getElementById('timerStatus');

    if (!startBtn || !stopBtn || !statusEl) return;

    if (status.timer_running) {
        startBtn.disabled = true;
        stopBtn.disabled = false;
        statusEl.textContent = '● RUNNING';
        statusEl.style.color = 'var(--neon-green)';
    } else {
        startBtn.disabled = false;
        stopBtn.disabled = true;
        statusEl.textContent = '■ STOPPED';
        statusEl.style.color = 'var(--neon-pink)';
    }
}

async function startTimer() {
    if (!publicKey || !ADMIN_WALLETS.includes(publicKey)) {
        addLog('Admin access required', 'error');
        return;
    }

    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'startTimer', wallet: publicKey })
        });

        const data = await response.json();
        if (data.success) {
            addLog('Timer started', 'success');
            await loadData(true);
        }
    } catch (error) {
        addLog('Failed to start timer', 'error');
    }
}

async function stopTimer() {
    if (!publicKey || !ADMIN_WALLETS.includes(publicKey)) {
        addLog('Admin access required', 'error');
        return;
    }

    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'stopTimer', wallet: publicKey })
        });

        const data = await response.json();
        if (data.success) {
            addLog('Timer stopped', 'warning');
            await loadData(true);
        }
    } catch (error) {
        addLog('Failed to stop timer', 'error');
    }
}

async function loadHolders(silent = false) {
    try {
        const response = await fetch('api.php?action=getHolders');
        holders = await response.json();

        document.getElementById('totalHolders').textContent = holders.total_holders.toLocaleString();
        document.getElementById('totalSupply').textContent = holders.total_supply.toLocaleString();

        const topHolders = holders.holders.slice(0, 20);
        const html = `
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Wallet</th>
                        <th>Balance</th>
                        <th>% of Supply</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${topHolders.map((h, i) => `
                        <tr style="${i === 0 ? 'opacity: 0.5;' : ''}">
                            <td>${i + 1}</td>
                            <td>${truncateAddress(h.owner)}</td>
                            <td>${h.uiAmount.toLocaleString(undefined, {maximumFractionDigits: 2})}</td>
                            <td>${((h.uiAmount / holders.total_supply) * 100).toFixed(2)}%</td>
                            <td>${i === 0 ? '<span style="color: var(--neon-pink);">EXCLUDED (LP)</span>' : '<span style="color: var(--neon-green);">✓</span>'}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
        document.getElementById('holdersList').innerHTML = html;
    } catch (error) {
        console.error('Failed to load holders:', error);
    }
}

async function loadHistory() {
    try {
        const response = await fetch('api.php?action=getHistory');
        const history = await response.json();

        if (history.length === 0) {
            document.getElementById('historyList').innerHTML = '<p>No distributions yet.</p>';
            return;
        }

        const html = `
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Recipients</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${history.map(d => `
                        <tr>
                            <td>${new Date(d.timestamp * 1000).toLocaleString()}</td>
                            <td>${d.total_amount.toFixed(4)} SOL</td>
                            <td>${d.recipients}</td>
                            <td>${d.status.toUpperCase()}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
        document.getElementById('historyList').innerHTML = html;
    } catch (error) {
        console.error('Failed to load history:', error);
    }
}

async function loadDaemonStatus() {
    try {
        const response = await fetch('api.php?action=getDaemonStatus');
        daemonStatus = await response.json();

        const statusEl = document.getElementById('daemonStatus');
        if (!statusEl) return;

        if (daemonStatus.running) {
            statusEl.innerHTML = '<span style="color: var(--neon-green);">● DAEMON RUNNING</span>';
        } else {
            statusEl.innerHTML = '<span style="color: var(--neon-pink);">■ DAEMON STOPPED</span>';
        }
    } catch (error) {
        console.error('Failed to load daemon status:', error);
    }
}

async function loadDaemonActivity() {
    try {
        const response = await fetch('api.php?action=getDaemonActivity');
        const activities = await response.json();

        if (!Array.isArray(activities)) return;

        // Add new daemon logs to activity panel
        activities.forEach(activity => {
            const logKey = `${activity.timestamp}-${activity.message}`;
            if (!displayedDaemonLogs.has(logKey)) {
                displayedDaemonLogs.add(logKey);
                const log = document.getElementById('activityLog');
                if (!log) return;

                const timestamp = new Date(activity.timestamp * 1000).toLocaleTimeString();
                const colors = {
                    info: 'var(--neon-cyan)',
                    success: 'var(--neon-green)',
                    error: 'var(--neon-pink)',
                    warning: '#ffaa00'
                };

                const entry = document.createElement('div');
                entry.style.marginBottom = '0.5rem';
                entry.innerHTML = `<span style="color: ${colors[activity.type] || colors.info};">[${timestamp}] [DAEMON]</span> ${activity.message}`;
                log.appendChild(entry);
                log.scrollTop = log.scrollHeight;

                // Keep last 100
                const entries = log.children;
                if (entries.length > 100) {
                    log.removeChild(entries[0]);
                }
            }
        });
    } catch (error) {
        console.error('Failed to load daemon activity:', error);
    }
}

async function loadRoyaltyStats() {
    try {
        const response = await fetch('api.php?action=getRoyaltyStats');
        const stats = await response.json();

        const solIcon = '<img src="https://uxwing.com/wp-content/themes/uxwing/download/brands-and-social-media/solana-sol-icon.png" class="sol-icon" alt="SOL">';

        // Update Royalty Info Box
        document.getElementById('lastClaimed').innerHTML =
            (stats.last_collection_amount || 0).toFixed(4) + solIcon;

        document.getElementById('totalCollected').innerHTML =
            (stats.total_collected || 0).toFixed(4) + solIcon;

        // Next drop = current treasury balance that will be distributed
        const statusResponse = await fetch('api.php?action=getStatus');
        const statusData = await statusResponse.json();

        document.getElementById('nextDrop').innerHTML =
            (statusData.distribution_amount || 0).toFixed(4) + solIcon;

    } catch (error) {
        console.error('Failed to load royalty stats:', error);
    }
}

async function loadTokenMetadata() {
    try {
        if (!status || !status.token_mint) return;

        // Use DAS API for better Pump.fun token support
        const response = await fetch(`https://mainnet.helius-rpc.com/?api-key=13eb1a93-5010-4ae1-9352-d4171b64a57b`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                jsonrpc: '2.0',
                id: 'token-metadata',
                method: 'getAsset',
                params: { id: status.token_mint }
            })
        });

        const data = await response.json();

        if (data.result) {
            const asset = data.result;
            const tokenInfo = document.getElementById('tokenInfo');
            const tokenImage = document.getElementById('tokenImage');
            const tokenName = document.getElementById('tokenName');
            const tokenTicker = document.getElementById('tokenTicker');

            // Extract metadata
            const name = asset.content?.metadata?.name || asset.content?.json_uri?.name || 'Unknown Token';
            const symbol = asset.content?.metadata?.symbol || asset.token_info?.symbol || '---';
            const image = asset.content?.links?.image || asset.content?.files?.[0]?.uri || '';

            tokenName.textContent = name;
            tokenTicker.textContent = symbol;

            if (image) {
                tokenImage.src = image;
                tokenImage.style.display = 'block';
            } else {
                tokenImage.style.display = 'none';
            }

            tokenInfo.style.display = 'block';
        }
    } catch (error) {
        console.error('Failed to load token metadata:', error);
    }
}

async function connectWallet() {
    if (!provider) {
        addLog('Phantom wallet not detected!', 'error');
        alert('Phantom wallet not found!');
        return;
    }

    try {
        const resp = await provider.connect();
        publicKey = resp.publicKey.toString();

        if (!ADMIN_WALLETS.includes(publicKey)) {
            addLog('Access denied', 'error');
            alert('Access denied. Admin wallet only.');
            provider.disconnect();
            return;
        }

        addLog('Admin connected: ' + truncateAddress(publicKey), 'success');
        document.getElementById('walletWidget').classList.remove('hidden');
        document.getElementById('walletAddress').textContent = truncateAddress(publicKey);
        document.getElementById('connectBtn').style.display = 'none';
        document.getElementById('executeBtn').style.display = 'block';
        document.getElementById('adminConfig').style.display = 'block';

        loadData();
        loadConfigForm();
    } catch (err) {
        addLog('Connection failed', 'error');
    }
}

function disconnectWallet() {
    if (provider) provider.disconnect();
    publicKey = null;
    document.getElementById('walletWidget').classList.add('hidden');
    document.getElementById('connectBtn').style.display = 'block';
    document.getElementById('executeBtn').style.display = 'none';
    document.getElementById('adminConfig').style.display = 'none';
}

async function loadConfigForm() {
    if (!status) return;
    document.getElementById('configInterval').value = status.interval_seconds;
    document.getElementById('configPercentage').value = status.distribution_percentage;
    document.getElementById('configEnabled').checked = status.enabled;
    document.getElementById('configTokenMint').value = status.token_mint || '';
}

async function saveConfiguration() {
    if (!publicKey || !ADMIN_WALLETS.includes(publicKey)) {
        addLog('Admin access required', 'error');
        return;
    }

    const interval = parseInt(document.getElementById('configInterval').value);
    const percentage = parseInt(document.getElementById('configPercentage').value);
    const enabled = document.getElementById('configEnabled').checked;
    const tokenMint = document.getElementById('configTokenMint').value.trim();

    if (!interval || interval < 1) {
        alert('Interval must be at least 1 second');
        return;
    }

    if (!percentage || percentage < 1 || percentage > 100) {
        alert('Percentage must be between 1 and 100');
        return;
    }

    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'updateConfig',
                wallet: publicKey,
                interval_seconds: interval,
                distribution_percentage: percentage,
                enabled: enabled,
                token_mint: tokenMint
            })
        });

        const data = await response.json();
        if (data.success) {
            addLog('Configuration saved', 'success');
            await loadData();

            // If timer is running, restart it to apply new interval
            if (status && status.timer_running) {
                addLog('Restarting timer with new interval...', 'info');
                await restartTimer();
            }
        }
    } catch (error) {
        addLog('Failed to save configuration', 'error');
    }
}

async function autoDistribute() {
    distributionInProgress = true;
    const timerGif = document.getElementById('timerGif');

    timerGif.src = 'https://cdn.cdnstep.com/uTxnRNiLe6APTZEPZilx/17.thumb256.webp';
    addLog('━━━ AUTO DISTRIBUTION STARTING ━━━', 'info');

    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'executeDistribution',
                wallet: ADMIN_WALLETS[0] // Use first admin wallet for auto
            })
        });

        const data = await response.json();

        if (data.success) {
            addLog(`✓ Distribution completed! ${data.total_amount.toFixed(4)} SOL to ${data.recipients} holders`, 'success');
        } else {
            addLog(`✗ Distribution failed: ${data.error}`, 'error');
            if (data.debug_output) {
                console.log('Distribution debug output:', data.debug_output);
                addLog(`Debug: ${data.distributions_count} recipients calculated`, 'warning');
            }
        }
    } catch (error) {
        addLog('✗ Distribution error: ' + error.message, 'error');
    } finally {
        timerGif.src = 'https://cdn.cdnstep.com/uTxnRNiLe6APTZEPZilx/15.thumb256.webp';
        addLog('━━━ Restarting timer for next cycle ━━━', 'info');

        // Restart timer for next cycle
        await restartTimer();
        distributionInProgress = false;
    }
}

async function restartTimer() {
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'startTimer',
                wallet: ADMIN_WALLETS[0]
            })
        });

        const data = await response.json();
        if (data.success) {
            await loadData(true);
        }
    } catch (error) {
        console.error('Failed to restart timer:', error);
    }
}

async function executeDistribution() {
    if (!publicKey || !ADMIN_WALLETS.includes(publicKey)) {
        addLog('Admin access required', 'error');
        return;
    }

    if (!confirm('Execute distribution now?')) {
        return;
    }

    const executeBtn = document.getElementById('executeBtn');
    const timerGif = document.getElementById('timerGif');

    executeBtn.disabled = true;
    executeBtn.textContent = 'EXECUTING...';
    timerGif.src = 'https://cdn.cdnstep.com/uTxnRNiLe6APTZEPZilx/17.thumb256.webp';

    addLog('Starting manual distribution...', 'info');

    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'executeDistribution', wallet: publicKey })
        });

        const data = await response.json();

        if (data.success) {
            addLog(`Distribution completed! ${data.total_amount.toFixed(4)} SOL to ${data.recipients} holders`, 'success');
            await loadData();
        } else {
            addLog(`Distribution failed: ${data.error}`, 'error');
        }
    } catch (error) {
        addLog('Distribution error', 'error');
    } finally {
        executeBtn.disabled = false;
        executeBtn.textContent = 'EXECUTE DISTRIBUTION NOW';
        timerGif.src = 'https://cdn.cdnstep.com/uTxnRNiLe6APTZEPZilx/15.thumb256.webp';
    }
}

function truncateAddress(address) {
    if (!address) return '';
    return address.slice(0, 4) + '...' + address.slice(-4);
}
