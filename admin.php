<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - is.gudtek.lol</title>
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
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            position: relative;
            z-index: 3;
        }

        h1, h2 {
            margin-bottom: 1rem;
            text-shadow: 0 0 10px var(--crt-glow);
        }

        .auth-section, .admin-panel {
            display: none;
        }

        .auth-section.active, .admin-panel.active {
            display: block;
        }

        button {
            background: transparent;
            color: var(--crt-green);
            border: 1px solid var(--crt-green);
            padding: 0.5rem 1rem;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            transition: all 0.2s;
            box-shadow: 0 0 5px var(--crt-glow);
            margin: 0.5rem 0.5rem 0.5rem 0;
        }

        button:hover {
            background: var(--crt-green);
            color: #000;
            box-shadow: 0 0 15px var(--crt-green);
        }

        input[type="text"], input[type="number"] {
            background: #000;
            color: var(--crt-green);
            border: 1px solid var(--crt-green);
            padding: 0.5rem;
            font-family: 'Courier New', monospace;
            font-size: 1rem;
            width: 100%;
            margin: 0.5rem 0;
            box-shadow: inset 0 0 10px var(--crt-glow);
        }

        input:focus {
            outline: none;
            box-shadow: 0 0 15px var(--crt-green);
        }

        .section {
            border: 1px solid var(--crt-green);
            padding: 1.5rem;
            margin: 1.5rem 0;
            background: rgba(0, 255, 0, 0.02);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            border: 1px solid var(--crt-green);
            padding: 0.75rem;
            text-align: left;
        }

        th {
            background: rgba(0, 255, 0, 0.1);
        }

        tr:hover {
            background: rgba(0, 255, 0, 0.05);
        }

        tr.deleted {
            opacity: 0.5;
            text-decoration: line-through;
        }

        .badge-deleted {
            background: #f00;
            color: #000;
            padding: 0.2rem 0.5rem;
            font-size: 0.7rem;
            border-radius: 3px;
            font-weight: bold;
        }

        .badge-active {
            background: var(--crt-green);
            color: #000;
            padding: 0.2rem 0.5rem;
            font-size: 0.7rem;
            border-radius: 3px;
            font-weight: bold;
        }

        .status {
            padding: 1rem;
            margin: 1rem 0;
            border: 1px solid var(--crt-green);
            display: none;
        }

        .status.error {
            border-color: #f00;
            color: #f00;
        }

        .status.success {
            border-color: #0f0;
            color: #0f0;
        }

        .btn-danger {
            border-color: #f00;
            color: #f00;
        }

        .btn-danger:hover {
            background: #f00;
            color: #000;
        }

        .wallet-info {
            padding: 1rem;
            border: 1px solid var(--crt-green);
            margin-bottom: 1rem;
            background: rgba(0, 255, 0, 0.05);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>[ ADMIN PANEL ]</h1>

        <div id="authSection" class="auth-section active">
            <div class="section">
                <h2>&gt; Authentication Required</h2>
                <p>Connect your Phantom wallet to access the admin panel.</p>
                <button id="connectBtn">CONNECT WALLET</button>
                <div id="authStatus" class="status"></div>
            </div>
        </div>

        <div id="adminPanel" class="admin-panel">
            <div class="wallet-info">
                <strong>Connected:</strong> <span id="walletAddress"></span>
                <button id="disconnectBtn" style="float: right;">DISCONNECT</button>
            </div>

            <div class="section">
                <h2>&gt; Configuration</h2>
                <div class="form-group">
                    <label>SOL Price:</label>
                    <input type="number" id="priceSol" step="0.01" placeholder="0.1">
                </div>
                <div class="form-group">
                    <label>Token Price:</label>
                    <input type="number" id="priceToken" step="1" placeholder="100">
                </div>
                <div class="form-group">
                    <label>Token Mint Address:</label>
                    <input type="text" id="tokenMint" placeholder="Enter Solana token mint address">
                </div>
                <button id="saveConfigBtn">SAVE CONFIGURATION</button>
                <div id="configStatus" class="status"></div>
            </div>

            <div class="section">
                <h2>&gt; Create Redirect / Reserve Domain (Free)</h2>
                <div class="form-group">
                    <label>Slug:</label>
                    <input type="text" id="adminSlug" placeholder="e.g., moon">
                </div>
                <div class="form-group">
                    <label>Target URL (optional - leave empty to reserve):</label>
                    <input type="text" id="adminTargetUrl" placeholder="https://example.com (or leave empty to reserve)">
                </div>
                <button id="createRedirectBtn">CREATE / RESERVE</button>
                <div id="createStatus" class="status"></div>
            </div>

            <div class="section">
                <h2>&gt; Redirect Management</h2>
                <div id="redirectsList">
                    <p>Loading...</p>
                </div>
            </div>

            <div class="section">
                <h2>&gt; Transaction History</h2>
                <div id="transactionsList">
                    <p>Loading...</p>
                </div>
            </div>

            <div class="section">
                <h2>&gt; Messages & Feature Requests</h2>
                <div id="messagesList">
                    <p>Loading...</p>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.9); z-index: 10001; padding: 2rem;">
            <div style="max-width: 600px; margin: 2rem auto; position: relative; z-index: 10002;">
                <div class="section">
                    <h2>&gt; Edit Domain</h2>
                    <div class="form-group">
                        <label>Slug:</label>
                        <input type="text" id="editSlug" readonly style="opacity: 0.5; cursor: not-allowed;">
                    </div>
                    <div class="form-group">
                        <label>Target URL (leave empty for reserved domain):</label>
                        <input type="text" id="editTargetUrl" placeholder="https://example.com or leave empty">
                    </div>
                    <input type="hidden" id="editId">
                    <button id="saveEditBtn">SAVE CHANGES</button>
                    <button id="cancelEditBtn" style="margin-left: 0.5rem;">CANCEL</button>
                    <div id="editStatus" class="status"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Proper Buffer polyfill for browser -->
    <script src="https://unpkg.com/buffer@6.0.3/index.js"></script>
    <script>
        window.Buffer = buffer.Buffer;
    </script>

    <script src="https://cdn.jsdelivr.net/npm/@solana/web3.js@1.87.6/lib/index.iife.min.js"></script>
    <script>
        const ADMIN_WALLET = '819ywRTzmw3Gfei4UgBbmw3FaNRVaPu8Npmz4bcRZFA6';
        let provider = null;
        let publicKey = null;

        document.addEventListener('DOMContentLoaded', () => {
            if ('phantom' in window) {
                const phantomProvider = window.phantom?.solana;
                if (phantomProvider?.isPhantom) {
                    provider = phantomProvider;
                }
            }

            document.getElementById('connectBtn').addEventListener('click', connectWallet);
            document.getElementById('disconnectBtn').addEventListener('click', disconnectWallet);
            document.getElementById('saveConfigBtn').addEventListener('click', saveConfig);
            document.getElementById('createRedirectBtn').addEventListener('click', createRedirect);
            document.getElementById('saveEditBtn').addEventListener('click', saveEdit);
            document.getElementById('cancelEditBtn').addEventListener('click', closeEditModal);
        });

        async function connectWallet() {
            if (!provider) {
                showStatus('authStatus', 'Phantom wallet not found!', 'error');
                return;
            }

            try {
                const resp = await provider.connect();
                publicKey = resp.publicKey.toString();

                if (publicKey !== ADMIN_WALLET) {
                    showStatus('authStatus', 'Access denied. Admin wallet only.', 'error');
                    provider.disconnect();
                    return;
                }

                document.getElementById('authSection').classList.remove('active');
                document.getElementById('adminPanel').classList.add('active');
                document.getElementById('walletAddress').textContent = truncateAddress(publicKey);

                await loadAdminData();
            } catch (err) {
                showStatus('authStatus', 'Connection failed: ' + err.message, 'error');
            }
        }

        function disconnectWallet() {
            if (provider) {
                provider.disconnect();
            }
            publicKey = null;
            document.getElementById('authSection').classList.add('active');
            document.getElementById('adminPanel').classList.remove('active');
        }

        async function loadAdminData() {
            await loadConfig();
            await loadRedirects();
            await loadTransactions();
            await loadMessages();
        }

        async function loadConfig() {
            try {
                const response = await fetch('api.php?action=getConfig');
                const config = await response.json();

                document.getElementById('priceSol').value = config.price_sol || '';
                document.getElementById('priceToken').value = config.price_token || '';
                document.getElementById('tokenMint').value = config.token_mint || '';
            } catch (error) {
                console.error('Failed to load config:', error);
            }
        }

        async function saveConfig() {
            const priceSol = document.getElementById('priceSol').value;
            const priceToken = document.getElementById('priceToken').value;
            const tokenMint = document.getElementById('tokenMint').value;

            try {
                const response = await fetch('admin_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'updateConfig',
                        wallet: publicKey,
                        config: {
                            price_sol: priceSol,
                            price_token: priceToken,
                            token_mint: tokenMint
                        }
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showStatus('configStatus', 'Configuration saved successfully!', 'success');
                } else {
                    showStatus('configStatus', 'Error: ' + data.error, 'error');
                }
            } catch (error) {
                showStatus('configStatus', 'Failed to save configuration', 'error');
            }
        }

        async function loadRedirects() {
            try {
                const response = await fetch('admin_api.php?action=getRedirects&wallet=' + publicKey);
                const redirects = await response.json();

                const html = `
                    <table>
                        <thead>
                            <tr>
                                <th>Slug</th>
                                <th>Target URL</th>
                                <th>Owner</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${redirects.map(r => `
                                <tr class="${r.active == 0 ? 'deleted' : ''}">
                                    <td>${escapeHtml(r.slug)}</td>
                                    <td>${r.target_url ? escapeHtml(r.target_url) : '<span style="opacity: 0.5;">[RESERVED]</span>'}</td>
                                    <td>${truncateAddress(r.owner_wallet)}</td>
                                    <td>
                                        ${r.active == 0
                                            ? '<span class="badge-deleted">DELETED</span>'
                                            : '<span class="badge-active">ACTIVE</span>'}
                                    </td>
                                    <td>${new Date(r.created_at * 1000).toLocaleDateString()}</td>
                                    <td>
                                        ${r.active == 1
                                            ? `<a href="https://${escapeHtml(r.slug)}.gudtek.lol" target="_blank" rel="noopener noreferrer">
                                                   <button style="margin-right: 0.25rem;">VISIT</button>
                                               </a>
                                               <button onclick="openEditModal(${r.id}, '${escapeHtml(r.slug)}', '${escapeHtml(r.target_url)}')">EDIT</button>
                                               <button class="btn-danger" onclick="deleteRedirect(${r.id}, '${escapeHtml(r.slug)}')">DELETE</button>`
                                            : '<span style="opacity: 0.5;">DELETED</span>'}
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;

                document.getElementById('redirectsList').innerHTML = html;
            } catch (error) {
                document.getElementById('redirectsList').innerHTML = '<p>Failed to load redirects</p>';
            }
        }

        async function loadTransactions() {
            try {
                const response = await fetch('admin_api.php?action=getTransactions&wallet=' + publicKey);
                const transactions = await response.json();

                const html = `
                    <table>
                        <thead>
                            <tr>
                                <th>Slug</th>
                                <th>Wallet</th>
                                <th>Amount</th>
                                <th>Currency</th>
                                <th>TX Signature</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${transactions.map(t => `
                                <tr>
                                    <td>${escapeHtml(t.slug)}</td>
                                    <td>${truncateAddress(t.wallet)}</td>
                                    <td>${t.amount}</td>
                                    <td>${t.currency.toUpperCase()}</td>
                                    <td>${t.tx_signature ? truncateAddress(t.tx_signature) : 'N/A'}</td>
                                    <td>${new Date(t.created_at * 1000).toLocaleDateString()}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;

                document.getElementById('transactionsList').innerHTML = html;
            } catch (error) {
                document.getElementById('transactionsList').innerHTML = '<p>Failed to load transactions</p>';
            }
        }

        async function loadMessages() {
            try {
                const response = await fetch('admin_api.php?action=getMessages&wallet=' + publicKey);
                const messages = await response.json();

                if (messages.length === 0) {
                    document.getElementById('messagesList').innerHTML = '<p>No messages yet.</p>';
                    return;
                }

                const html = `
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>From</th>
                                <th>Message</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${messages.map(m => `
                                <tr style="${m.status === 'new' ? 'background: rgba(0, 255, 0, 0.1);' : ''}">
                                    <td>${new Date(m.created_at * 1000).toLocaleDateString()} ${new Date(m.created_at * 1000).toLocaleTimeString()}</td>
                                    <td>${m.wallet === 'anonymous' ? '<span style="opacity: 0.5;">Anonymous</span>' : truncateAddress(m.wallet)}</td>
                                    <td style="max-width: 400px; word-wrap: break-word; white-space: pre-wrap;">${escapeHtml(m.message)}</td>
                                    <td>
                                        ${m.status === 'new'
                                            ? '<span class="badge-active">NEW</span>'
                                            : '<span style="opacity: 0.5;">READ</span>'}
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;

                document.getElementById('messagesList').innerHTML = html;
            } catch (error) {
                document.getElementById('messagesList').innerHTML = '<p>Failed to load messages</p>';
            }
        }

        async function deleteRedirect(id, slug) {
            if (!confirm(`Are you sure you want to delete "${slug}"?`)) {
                return;
            }

            try {
                const response = await fetch('admin_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'deleteRedirect',
                        wallet: publicKey,
                        id: id
                    })
                });

                const data = await response.json();

                if (data.success) {
                    await loadRedirects();
                } else {
                    alert('Failed to delete: ' + data.error);
                }
            } catch (error) {
                alert('Failed to delete redirect');
            }
        }

        async function createRedirect() {
            const slug = document.getElementById('adminSlug').value.toLowerCase().trim();
            const targetUrl = document.getElementById('adminTargetUrl').value.trim();

            if (!slug) {
                showStatus('createStatus', 'Please enter a slug', 'error');
                return;
            }

            if (!/^[a-z0-9-]+$/.test(slug)) {
                showStatus('createStatus', 'Invalid slug format (lowercase letters, numbers, hyphens only)', 'error');
                return;
            }

            if (targetUrl && !targetUrl.startsWith('http://') && !targetUrl.startsWith('https://')) {
                showStatus('createStatus', 'URL must start with http:// or https://', 'error');
                return;
            }

            try {
                const response = await fetch('admin_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'createRedirect',
                        wallet: publicKey,
                        slug: slug,
                        targetUrl: targetUrl
                    })
                });

                const data = await response.json();

                if (data.success) {
                    const message = data.message || `Success! ${slug}.gudtek.lol is now live`;
                    showStatus('createStatus', message, 'success');
                    document.getElementById('adminSlug').value = '';
                    document.getElementById('adminTargetUrl').value = '';
                    await loadRedirects();
                } else {
                    showStatus('createStatus', 'Error: ' + data.error, 'error');
                }
            } catch (error) {
                showStatus('createStatus', 'Failed to create redirect', 'error');
            }
        }

        function openEditModal(id, slug, targetUrl) {
            document.getElementById('editId').value = id;
            document.getElementById('editSlug').value = slug;
            document.getElementById('editTargetUrl').value = targetUrl;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
            document.getElementById('editStatus').style.display = 'none';
        }

        async function saveEdit() {
            const id = document.getElementById('editId').value;
            const targetUrl = document.getElementById('editTargetUrl').value.trim();
            const slug = document.getElementById('editSlug').value;

            if (targetUrl && !targetUrl.startsWith('http://') && !targetUrl.startsWith('https://')) {
                showStatus('editStatus', 'URL must start with http:// or https://', 'error');
                return;
            }

            try {
                const response = await fetch('admin_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'updateRedirect',
                        wallet: publicKey,
                        id: parseInt(id),
                        targetUrl: targetUrl
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showStatus('editStatus', 'Updated successfully!', 'success');
                    setTimeout(() => {
                        closeEditModal();
                        loadRedirects();
                    }, 1000);
                } else {
                    showStatus('editStatus', 'Error: ' + data.error, 'error');
                }
            } catch (error) {
                showStatus('editStatus', 'Failed to update redirect', 'error');
            }
        }

        function showStatus(elementId, message, type) {
            const el = document.getElementById(elementId);
            el.textContent = message;
            el.className = 'status ' + type;
            el.style.display = 'block';

            setTimeout(() => {
                el.style.display = 'none';
            }, 5000);
        }

        function truncateAddress(address) {
            return address.slice(0, 4) + '...' + address.slice(-4);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
