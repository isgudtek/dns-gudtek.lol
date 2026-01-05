// Phantom Wallet Integration
let provider = null;
let publicKey = null;
let config = {};
let solPrice = 0;
let tokenPrice = 0;
let calculatedTokenAmount = 0;
let currentSlugPrice = { sol: 0, token: 0, multiplier: 1 };

// Calculate price based on domain length
function calculateDomainPrice(slug) {
    if (!config.price_sol || !config.price_token) {
        return { sol: 0, token: 0, multiplier: 1 };
    }

    const baseSolPrice = parseFloat(config.price_sol);
    const baseTokenPrice = parseFloat(config.price_token);

    let multiplier = 1;
    const length = slug.length;

    if (length === 1) multiplier = 10;
    else if (length === 2) multiplier = 5;
    else if (length === 3) multiplier = 4;
    else if (length === 4) multiplier = 3;
    else if (length === 5) multiplier = 2;
    else multiplier = 1;

    const solAmount = baseSolPrice * multiplier;

    // Calculate token amount based on USD equivalent
    let tokenAmount;
    if (solPrice > 0 && tokenPrice > 0) {
        const solValueUsd = solAmount * solPrice;
        tokenAmount = Math.floor(solValueUsd / tokenPrice);
    } else {
        tokenAmount = baseTokenPrice * multiplier;
    }

    return {
        sol: solAmount,
        token: tokenAmount,
        multiplier: multiplier
    };
}

// Custom Modal Functions
function showModal(title, message, buttons) {
    const overlay = document.getElementById('modalOverlay');
    const titleEl = document.getElementById('modalTitle');
    const messageEl = document.getElementById('modalMessage');
    const buttonsEl = document.getElementById('modalButtons');

    titleEl.textContent = `[ ${title} ]`;
    messageEl.innerHTML = message;
    buttonsEl.innerHTML = '';

    buttons.forEach(btn => {
        const button = document.createElement('button');
        button.textContent = btn.text;
        button.onclick = () => {
            overlay.classList.remove('active');
            if (btn.callback) btn.callback();
        };
        buttonsEl.appendChild(button);
    });

    overlay.classList.add('active');

    // Close on ESC key
    const escHandler = (e) => {
        if (e.key === 'Escape') {
            overlay.classList.remove('active');
            document.removeEventListener('keydown', escHandler);
        }
    };
    document.addEventListener('keydown', escHandler);

    // Close on click outside
    overlay.onclick = (e) => {
        if (e.target === overlay) {
            overlay.classList.remove('active');
        }
    };
}

function modalAlert(message, title = 'ALERT') {
    showModal(title, message, [{ text: 'OK', callback: null }]);
}

function modalConfirm(message, onConfirm, title = 'CONFIRM') {
    showModal(title, message, [
        { text: 'CANCEL', callback: null },
        { text: 'OK', callback: onConfirm }
    ]);
}

function modalSuccess(message, linkUrl = null, linkText = 'TEST LINK') {
    let msg = message;
    if (linkUrl) {
        msg += `<br><br><a class="modal-link" href="${linkUrl}" target="_blank">${linkText}</a>`;
    }
    showModal('SUCCESS', msg, [{ text: 'CLOSE', callback: null }]);
}

// Make this function globally accessible
window.showHowItWorksModal = function() {
    const message = `
        <div style="text-align: left; line-height: 1.8;">
            <p><strong>🎯 What is this?</strong></p>
            <p>This is like a nickname for websites! Instead of remembering a long website address, you can make a short, easy name like "moon.gudtek.lol" that takes you there.</p>

            <p style="margin-top: 1rem;"><strong>📝 How to create your domain:</strong></p>
            <p>1. Connect your Phantom wallet<br>
            2. Pick a cool name (like "meme" or "pump")<br>
            3. Enter the website you want it to go to<br>
            4. Pay with SOL or tokens<br>
            5. Done! Your domain is live!</p>

            <p style="margin-top: 1rem;"><strong>⚠️ Important rules:</strong></p>
            <p>• You <strong>cannot edit</strong> a domain after creating it (until the marketplace will be available)<br>
            • You <strong>can delete</strong> it if you want<br>
            • If you delete it, it's gone forever (but you can register it again)<br>
            • Domains are first-come, first-served!</p>

            <div style="margin-top: 1.5rem; padding: 1rem; border: 2px solid #f00; background: rgba(255, 0, 0, 0.1);">
                <p style="color: #f00;"><strong>🚫 FORBIDDEN CONTENT:</strong></p>
                <p style="margin-top: 0.5rem;">Do NOT create domains linking to illegal content, scams, malware, or anything harmful. We will remove these domains and may ban your wallet. Be responsible!</p>
            </div>

            <p style="margin-top: 1rem; opacity: 0.8; font-size: 0.9rem;">Questions? Contact us or check the docs.</p>
        </div>
    `;
    showModal('HOW DOES IT WORK?', message, [{ text: 'GOT IT!', callback: null }]);
};

// Typewriter effect for header
function typewriterEffect() {
    const domains = [
        'meme',
        'pump',
        'moon',
        'degen',
        'hodl',
        'wagmi',
        'ngmi',
        'gm',
        'wen',
        'ser'
    ];

    let domainIndex = 0;
    let charIndex = 0;
    let isDeleting = false;
    const typeSpeed = 100;
    const deleteSpeed = 50;
    const pauseTime = 2000;

    const typewriterEl = document.getElementById('typewriter');

    if (!typewriterEl) {
        console.error('Typewriter element not found');
        return;
    }

    console.log('Typewriter element found, starting animation...');

    function type() {
        const currentDomain = domains[domainIndex];

        if (isDeleting) {
            typewriterEl.textContent = currentDomain.substring(0, charIndex - 1);
            charIndex--;
        } else {
            typewriterEl.textContent = currentDomain.substring(0, charIndex + 1);
            charIndex++;
        }

        let timeout = isDeleting ? deleteSpeed : typeSpeed;

        if (!isDeleting && charIndex === currentDomain.length) {
            timeout = pauseTime;
            isDeleting = true;
        } else if (isDeleting && charIndex === 0) {
            isDeleting = false;
            domainIndex = (domainIndex + 1) % domains.length;
        }

        setTimeout(type, timeout);
    }

    // Start typing immediately
    type();
}

// Initialize app
document.addEventListener('DOMContentLoaded', async () => {
    console.log('DOM loaded, starting typewriter...');

    // Start typewriter effect
    typewriterEffect();

    // Check for Phantom
    if ('phantom' in window) {
        const phantomProvider = window.phantom?.solana;
        if (phantomProvider?.isPhantom) {
            provider = phantomProvider;
        }
    }

    // Load config and domains
    await loadConfig();
    await loadDomains();

    // Event listeners
    document.getElementById('connectBtn').addEventListener('click', connectWallet);
    document.getElementById('disconnectBtn').addEventListener('click', disconnectWallet);
    document.getElementById('slugInput').addEventListener('input', checkSlugAvailability);
    document.getElementById('paySolBtn').addEventListener('click', () => purchaseSlug('sol'));
    document.getElementById('payTokenBtn').addEventListener('click', () => purchaseSlug('token'));
    document.getElementById('payStripeBtn').addEventListener('click', () => purchaseWithStripe());

    // Hero availability checker
    document.getElementById('heroSlugInput').addEventListener('input', checkHeroAvailability);

    // Show hero checker initially
    document.getElementById('heroAvailabilityChecker').style.display = 'block';

    // Auto-connect if previously connected
    if (provider && provider.isConnected) {
        await connectWallet();
    }
});

// Fetch SOL price from CoinGecko (free, no auth)
async function fetchSolPrice() {
    try {
        const response = await fetch('https://api.coingecko.com/api/v3/simple/price?ids=solana&vs_currencies=usd');
        const data = await response.json();
        if (data.solana && data.solana.usd) {
            solPrice = data.solana.usd;
            console.log('SOL Price:', solPrice);
            updatePriceDisplay();
        }
    } catch (error) {
        console.error('Failed to fetch SOL price:', error);
    }
}

// Fetch token price from DexScreener (free, no auth required)
async function fetchTokenPrice(tokenMint) {
    const priceEl = document.getElementById('tokenPrice');

    try {
        const dexResponse = await fetch(`https://api.dexscreener.com/latest/dex/tokens/${tokenMint}`);
        const dexData = await dexResponse.json();

        if (dexData.pairs && dexData.pairs.length > 0) {
            // Get the pair with highest liquidity
            const bestPair = dexData.pairs.sort((a, b) =>
                parseFloat(b.liquidity?.usd || 0) - parseFloat(a.liquidity?.usd || 0)
            )[0];

            if (bestPair.priceUsd) {
                tokenPrice = parseFloat(bestPair.priceUsd);
                displayPrice(tokenPrice, priceEl);
                updatePriceDisplay();
                return;
            }
        }

        priceEl.textContent = 'Price not available (no liquidity)';
    } catch (error) {
        console.error('Failed to fetch token price:', error);
        priceEl.textContent = 'Price unavailable';
    }
}

function displayPrice(price, element) {
    if (price < 0.00001) {
        element.innerHTML = `Price: $${price.toExponential(2)} USD`;
    } else if (price < 1) {
        element.innerHTML = `Price: $${price.toFixed(6)} USD`;
    } else {
        element.innerHTML = `Price: $${price.toFixed(4)} USD`;
    }
}

// Update price display with calculated token amounts
function updatePriceDisplay() {
    if (!config.price_sol || !config.price_token) return;

    const solAmount = parseFloat(config.price_sol);
    const tokenAmountConfig = parseFloat(config.price_token);

    if (solPrice > 0 && tokenPrice > 0) {
        // Calculate equivalent token amount based on SOL price
        const solValueUsd = solAmount * solPrice;
        calculatedTokenAmount = Math.floor(solValueUsd / tokenPrice);

        const tokenValueUsd = calculatedTokenAmount * tokenPrice;

        const stripePrice = Math.max(0.50, solAmount).toFixed(2);

        document.getElementById('priceInfo').innerHTML = `
            <strong>Pricing from:</strong><br>
            ${solAmount} SOL (≈ $${solValueUsd.toFixed(2)} USD)<br>
            <em style="opacity: 0.8; font-size: 0.9rem;">or</em><br>
            ${calculatedTokenAmount.toLocaleString()} TOKEN (≈ $${tokenValueUsd.toFixed(2)} USD)<br>
            <em style="opacity: 0.8; font-size: 0.9rem;">or</em><br>
            <span style="color: #635bff;">$${stripePrice} USD via Card 💳</span>
        `;

        console.log('Calculated token amount:', calculatedTokenAmount);
    } else {
        const stripePrice = Math.max(0.50, solAmount).toFixed(2);
        document.getElementById('priceInfo').innerHTML = `
            <strong>Pricing from:</strong><br>
            ${solAmount} SOL or ${tokenAmountConfig} TOKEN<br>
            <em style="opacity: 0.8; font-size: 0.9rem;">or</em><br>
            <span style="color: #635bff;">$${stripePrice} USD via Card 💳</span><br>
            <em style="opacity: 0.7; font-size: 0.85rem;">Fetching live prices...</em>
        `;
    }
}

// Load configuration from backend
async function loadConfig() {
    try {
        const response = await fetch('api.php?action=getConfig');
        config = await response.json();
        console.log('Config loaded:', config);

        if (config.price_sol && config.price_token) {
            updatePriceDisplay();
            // Fetch SOL price for calculations
            fetchSolPrice();
        } else {
            console.error('Config missing prices:', config);
            document.getElementById('priceInfo').innerHTML = `
                <strong>Error:</strong> Pricing not configured
            `;
        }

        // Display token mint address and fetch price
        if (config.token_mint && config.token_mint !== '') {
            document.getElementById('tokenMintDisplay').innerHTML = `
                <span style="display: block; margin-bottom: 0.5rem;">${config.token_mint}</span>
                <span style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-size: 0.9rem;">
                    <span id="tokenPrice" style="opacity: 0.8;">Fetching price...</span>
                    <a href="https://pump.fun/coin/${config.token_mint}" target="_blank" style="
                        background: #ff0000;
                        color: #fff;
                        padding: 0.25rem 0.6rem;
                        border-radius: 4px;
                        text-decoration: none;
                        font-weight: bold;
                        font-size: 0.75rem;
                        transition: all 0.2s;
                        box-shadow: 0 0 10px rgba(255, 0, 0, 0.5);
                    " onmouseover="this.style.boxShadow='0 0 15px rgba(255, 0, 0, 0.8)'; this.style.transform='scale(1.05)'" onmouseout="this.style.boxShadow='0 0 10px rgba(255, 0, 0, 0.5)'; this.style.transform='scale(1)'">BUY</a>
                </span>
            `;

            // Fetch token price immediately
            fetchTokenPrice(config.token_mint);

            // Refresh prices every 10 seconds
            setInterval(() => {
                fetchTokenPrice(config.token_mint);
                fetchSolPrice();
            }, 10000);
        } else {
            document.getElementById('tokenMintDisplay').textContent = 'Not configured';
        }
    } catch (error) {
        console.error('Failed to load config:', error);
    }
}

// Load recent domains
async function loadDomains() {
    try {
        const response = await fetch('api.php?action=getDomains');
        const domains = await response.json();

        const domainsList = document.getElementById('domainsList');

        if (domains.length === 0) {
            domainsList.innerHTML = '<div class="domain-item">No domains created yet. Be the first!</div>';
            return;
        }

        domainsList.innerHTML = domains.map(d => `
            <div class="domain-item">
                <a href="https://${escapeHtml(d.slug)}.gudtek.lol" target="_blank" class="domain-slug">${escapeHtml(d.slug)}.gudtek.lol</a>
                <span class="domain-url">${escapeHtml(truncateUrl(d.target_url))}</span>
            </div>
        `).join('');
    } catch (error) {
        console.error('Failed to load domains:', error);
    }
}

// Load user's own domains
async function loadMyDomains() {
    if (!publicKey) return;

    try {
        const response = await fetch(`api.php?action=getMyDomains&wallet=${publicKey}`);
        const domains = await response.json();

        const myDomainsList = document.getElementById('myDomainsList');

        if (domains.length === 0) {
            myDomainsList.innerHTML = '<div class="domain-item">You haven\'t created any domains yet.</div>';
            return;
        }

        myDomainsList.innerHTML = domains.map(d => `
            <div class="domain-item">
                <a href="https://${escapeHtml(d.slug)}.gudtek.lol" target="_blank" class="domain-slug">${escapeHtml(d.slug)}.gudtek.lol</a>
                <span class="domain-url">${escapeHtml(truncateUrl(d.target_url))}</span>
                <button class="btn-delete" onclick="deleteMyDomain('${escapeHtml(d.slug)}')">✕</button>
            </div>
        `).join('');
    } catch (error) {
        console.error('Failed to load my domains:', error);
    }
}

// Delete user's own domain
async function deleteMyDomain(slug) {
    modalConfirm(`Delete ${slug}.gudtek.lol?<br><br>This action cannot be undone.`, async () => {
        await executeDelete(slug);
    }, 'DELETE REDIRECT');
}

async function executeDelete(slug) {

    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'deleteMyDomain',
                slug: slug,
                wallet: publicKey
            })
        });

        const data = await response.json();

        if (data.success) {
            await loadMyDomains();
            await loadDomains();
            showStatus('Domain deleted successfully', 'success');
        } else {
            modalAlert('Failed to delete: ' + data.error, 'ERROR');
        }
    } catch (error) {
        console.error('Delete failed:', error);
        modalAlert('Failed to delete domain', 'ERROR');
    }
}

// Connect to Phantom wallet
async function connectWallet() {
    if (!provider) {
        modalAlert('Phantom wallet not found!<br><br>Please install it from <a href="https://phantom.app" target="_blank" class="modal-link">phantom.app</a>', 'WALLET NOT FOUND');
        return;
    }

    try {
        // Check if already connected
        let resp;
        if (provider.isConnected && provider.publicKey) {
            // Already connected, just use existing connection
            resp = { publicKey: provider.publicKey };
        } else {
            // Disconnect first to reset state (in case of stale connection)
            try {
                await provider.disconnect();
            } catch (e) {
                // Ignore disconnect errors
            }

            // Wait a moment for disconnect to complete
            await new Promise(resolve => setTimeout(resolve, 100));

            // Now connect fresh
            resp = await provider.connect({ onlyIfTrusted: false });
        }

        publicKey = resp.publicKey.toString();

        // Update UI
        document.getElementById('walletWidget').classList.remove('hidden');
        document.getElementById('purchaseSection').classList.add('active');
        document.getElementById('myDomainsSection').classList.add('active');
        document.getElementById('connectBtn').style.display = 'none';
        document.getElementById('heroAvailabilityChecker').style.display = 'none';
        document.getElementById('walletAddress').textContent = truncateAddress(publicKey);

        // Show admin link if connected wallet is admin
        const ADMIN_WALLET = '819ywRTzmw3Gfei4UgBbmw3FaNRVaPu8Npmz4bcRZFA6';
        if (publicKey === ADMIN_WALLET) {
            document.getElementById('adminLink').style.display = 'block';
        } else {
            document.getElementById('adminLink').style.display = 'none';
        }

        // Load balances and user's domains
        await updateBalances();
        await loadMyDomains();
    } catch (err) {
        console.error('Connection failed:', err);
        modalAlert('Failed to connect wallet.<br><br>Please try refreshing the page.', 'CONNECTION ERROR');
    }
}

// Disconnect wallet
async function disconnectWallet() {
    try {
        if (provider && provider.isConnected) {
            await provider.disconnect();
        }

        publicKey = null;
        document.getElementById('walletWidget').classList.add('hidden');
        document.getElementById('purchaseSection').classList.remove('active');
        document.getElementById('myDomainsSection').classList.remove('active');
        document.getElementById('connectBtn').style.display = 'block';
        document.getElementById('heroAvailabilityChecker').style.display = 'block';
        document.getElementById('adminLink').style.display = 'none';

        // Reset balance display
        document.getElementById('solBalance').textContent = '...';
        document.getElementById('tokenBalance').textContent = '...';
    } catch (err) {
        console.error('Disconnect error:', err);
        // Force reset UI anyway
        publicKey = null;
        document.getElementById('walletWidget').classList.add('hidden');
        document.getElementById('purchaseSection').classList.remove('active');
        document.getElementById('myDomainsSection').classList.remove('active');
        document.getElementById('connectBtn').style.display = 'block';
        document.getElementById('heroAvailabilityChecker').style.display = 'block';
        document.getElementById('adminLink').style.display = 'none';
    }
}

// Update SOL and token balances with Helius
async function updateBalances() {
    if (!publicKey || !provider) return;

    document.getElementById('solBalance').textContent = '...';
    document.getElementById('tokenBalance').textContent = '...';

    try {
        const connection = new solanaWeb3.Connection(
            'https://mainnet.helius-rpc.com/?api-key=13eb1a93-5010-4ae1-9352-d4171b64a57b',
            'confirmed'
        );

        const pubKey = new solanaWeb3.PublicKey(publicKey);
        const balance = await connection.getBalance(pubKey);
        document.getElementById('solBalance').textContent = (balance / solanaWeb3.LAMPORTS_PER_SOL).toFixed(4);

        if (config.token_mint && config.token_mint !== '') {
            try {
                const tokenMint = new solanaWeb3.PublicKey(config.token_mint);
                const tokenAccounts = await connection.getParsedTokenAccountsByOwner(pubKey, { mint: tokenMint });

                if (tokenAccounts.value.length > 0) {
                    document.getElementById('tokenBalance').textContent = tokenAccounts.value[0].account.data.parsed.info.tokenAmount.uiAmount.toFixed(2);
                } else {
                    document.getElementById('tokenBalance').textContent = '0.00';
                }
            } catch (err) {
                document.getElementById('tokenBalance').textContent = 'N/A';
            }
        } else {
            document.getElementById('tokenBalance').textContent = 'Not configured';
        }
    } catch (error) {
        console.error('Balance error:', error);
        document.getElementById('solBalance').textContent = 'Connected';
        document.getElementById('tokenBalance').textContent = '-';
    }
}

// Reserved subdomains that cannot be purchased
const RESERVED_DOMAINS = ['is', 'www', 'mail', 'smtp', 'pop', 'imap', 'ftp', 'admin', 'api', 'dev', 'staging', 'test', 'demo', 'blog', 'shop', 'store', 'cdn', 'static', 'assets', 'images', 'img', 'css', 'js'];

// Hero availability checker (for non-logged-in users)
let heroCheckTimeout = null;
async function checkHeroAvailability() {
    clearTimeout(heroCheckTimeout);

    const slug = document.getElementById('heroSlugInput').value.toLowerCase().trim();
    const statusEl = document.getElementById('heroAvailabilityStatus');

    if (slug.length === 0) {
        statusEl.textContent = '';
        statusEl.className = 'availability-status';
        return;
    }

    // Validate slug format
    if (!/^[a-z0-9-]+$/.test(slug)) {
        statusEl.textContent = '✗ Only lowercase letters, numbers, and hyphens allowed';
        statusEl.className = 'availability-status unavailable';
        return;
    }

    // Check if reserved
    if (RESERVED_DOMAINS.includes(slug)) {
        statusEl.textContent = '✗ This domain name is reserved';
        statusEl.className = 'availability-status unavailable';
        return;
    }

    statusEl.textContent = 'Checking...';
    statusEl.className = 'availability-status checking';

    heroCheckTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`api.php?action=checkSlug&slug=${encodeURIComponent(slug)}`);
            const data = await response.json();

            if (data.available) {
                const pricing = calculateDomainPrice(slug);
                const multiplierText = pricing.multiplier > 1 ? ` (${pricing.multiplier}x premium)` : '';

                statusEl.innerHTML = `
                    <div style="text-align: center; margin-bottom: 0.75rem;">
                        ✓ <strong>${slug}.gudtek.lol</strong> is available!${multiplierText}
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
                        <div style="font-size: 0.9rem; opacity: 0.9;">
                            for ${pricing.sol} SOL or ${pricing.token.toLocaleString()} TOKEN
                        </div>
                        <button onclick="connectWallet()" style="
                            background: transparent;
                            color: var(--crt-green);
                            border: 1px solid var(--crt-green);
                            padding: 0.6rem 1rem;
                            cursor: pointer;
                            font-family: 'Courier New', monospace;
                            font-size: 0.8rem;
                            transition: all 0.2s;
                            box-shadow: 0 0 10px rgba(0, 255, 0, 0.3);
                            font-weight: bold;
                            white-space: nowrap;
                        " onmouseover="this.style.background='var(--crt-green)'; this.style.color='#000'; this.style.boxShadow='0 0 20px var(--crt-green)'" onmouseout="this.style.background='transparent'; this.style.color='var(--crt-green)'; this.style.boxShadow='0 0 10px rgba(0, 255, 0, 0.3)'">CONNECT</button>
                    </div>
                `;
                statusEl.className = 'availability-status available';
            } else {
                statusEl.textContent = '✗ Already taken';
                statusEl.className = 'availability-status unavailable';
            }
        } catch (error) {
            console.error('Failed to check availability:', error);
            statusEl.textContent = '✗ Error checking availability';
            statusEl.className = 'availability-status unavailable';
        }
    }, 500);
}

// Check slug availability
let checkTimeout;
async function checkSlugAvailability() {
    clearTimeout(checkTimeout);

    const slug = document.getElementById('slugInput').value.toLowerCase().trim();
    const statusEl = document.getElementById('slugStatus');

    if (slug.length === 0) {
        statusEl.textContent = '';
        statusEl.className = '';
        return;
    }

    // Validate slug format
    if (!/^[a-z0-9-]+$/.test(slug)) {
        statusEl.textContent = '✗ Only lowercase letters, numbers, and hyphens allowed';
        statusEl.className = 'unavailable';
        return;
    }

    // Check if reserved
    if (RESERVED_DOMAINS.includes(slug)) {
        statusEl.textContent = '✗ This domain name is reserved';
        statusEl.className = 'unavailable';
        return;
    }

    checkTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`api.php?action=checkSlug&slug=${encodeURIComponent(slug)}`);
            const data = await response.json();

            if (data.available) {
                const pricing = calculateDomainPrice(slug);
                currentSlugPrice = pricing;

                const multiplierText = pricing.multiplier > 1 ? ` (${pricing.multiplier}x premium)` : '';

                statusEl.innerHTML = `
                    ✓ Available!${multiplierText}<br>
                    <small style="opacity: 0.9;">Price: ${pricing.sol} SOL or ${pricing.token.toLocaleString()} TOKEN</small>
                `;
                statusEl.className = 'available';
            } else {
                statusEl.textContent = '✗ Already taken';
                statusEl.className = 'unavailable';
                currentSlugPrice = { sol: 0, token: 0, multiplier: 1 };
            }
        } catch (error) {
            console.error('Failed to check slug:', error);
        }
    }, 500);
}

// Purchase slug
async function purchaseSlug(currency) {
    const slug = document.getElementById('slugInput').value.toLowerCase().trim();
    const targetUrl = document.getElementById('urlInput').value.trim();
    const statusMsg = document.getElementById('statusMsg');

    console.log('Purchase attempt:', { slug, targetUrl, currency });

    // Validation
    if (!publicKey) {
        showStatus('Please connect your wallet first', 'error');
        return;
    }

    if (!slug || slug.length === 0) {
        showStatus('Please enter a domain name', 'error');
        return;
    }

    if (slug.length < 2) {
        showStatus('Domain name must be at least 2 characters', 'error');
        return;
    }

    if (!/^[a-z0-9-]+$/.test(slug)) {
        showStatus('Invalid format (only lowercase letters, numbers, and hyphens)', 'error');
        return;
    }

    // Check if reserved
    if (RESERVED_DOMAINS.includes(slug)) {
        showStatus('This domain name is reserved', 'error');
        return;
    }

    if (!targetUrl.startsWith('http://') && !targetUrl.startsWith('https://')) {
        showStatus('Please enter a valid URL (must start with http:// or https://)', 'error');
        return;
    }

    try {
        // Validate price is loaded
        if (!config.price_sol || isNaN(parseFloat(config.price_sol))) {
            showStatus('Error: Price not loaded. Please refresh the page.', 'error');
            console.error('Config:', config);
            return;
        }

        showStatus('Processing payment...', 'success');

        const connection = new solanaWeb3.Connection(
            'https://mainnet.helius-rpc.com/?api-key=13eb1a93-5010-4ae1-9352-d4171b64a57b',
            'confirmed'
        );

        const treasuryWallet = new solanaWeb3.PublicKey('819ywRTzmw3Gfei4UgBbmw3FaNRVaPu8Npmz4bcRZFA6');

        if (currency === 'sol') {
            // Use calculated price based on domain length
            const pricing = currentSlugPrice.sol > 0 ? currentSlugPrice : calculateDomainPrice(slug);
            const priceInSol = pricing.sol;
            console.log('Price in SOL:', priceInSol, '(multiplier:', pricing.multiplier, ')');

            const amount = Math.floor(priceInSol * solanaWeb3.LAMPORTS_PER_SOL);

            const transaction = new solanaWeb3.Transaction().add(
                solanaWeb3.SystemProgram.transfer({
                    fromPubkey: new solanaWeb3.PublicKey(publicKey),
                    toPubkey: treasuryWallet,
                    lamports: amount
                })
            );

            const { blockhash } = await connection.getLatestBlockhash();
            transaction.recentBlockhash = blockhash;
            transaction.feePayer = new solanaWeb3.PublicKey(publicKey);

            const signed = await provider.signAndSendTransaction(transaction);

            showStatus('Verifying payment...', 'success');

            // Wait for confirmation
            await connection.confirmTransaction(signed.signature);

            // Register purchase (backend will verify with Helius before activating)
            await registerPurchase(slug, targetUrl, signed.signature, currency);

        } else if (currency === 'token') {
            if (!config.token_mint || config.token_mint === '') {
                showStatus('Token not configured', 'error');
                return;
            }

            // Use calculated price based on domain length
            const pricing = currentSlugPrice.token > 0 ? currentSlugPrice : calculateDomainPrice(slug);
            const tokenAmount = pricing.token;
            console.log('Token payment:', tokenAmount, '(multiplier:', pricing.multiplier, ')');

            // Get token decimals (most Solana tokens have 9 decimals, but we'll assume standard)
            const decimals = 9; // Standard SPL token decimals
            const amount = Math.floor(tokenAmount * Math.pow(10, decimals));

            // Create SPL Token transfer instruction
            const tokenMint = new solanaWeb3.PublicKey(config.token_mint);
            const fromTokenAccount = await connection.getParsedTokenAccountsByOwner(
                new solanaWeb3.PublicKey(publicKey),
                { mint: tokenMint }
            );

            if (fromTokenAccount.value.length === 0) {
                showStatus('You don\'t have any of this token in your wallet', 'error');
                return;
            }

            const sourceAccount = fromTokenAccount.value[0].pubkey;

            // Get or create treasury token account
            const treasuryTokenAccounts = await connection.getParsedTokenAccountsByOwner(
                treasuryWallet,
                { mint: tokenMint }
            );

            if (treasuryTokenAccounts.value.length === 0) {
                showStatus('Treasury token account not set up. Please contact admin.', 'error');
                return;
            }

            const destAccount = treasuryTokenAccounts.value[0].pubkey;

            // Create SPL Token transfer instruction manually
            const TOKEN_PROGRAM_ID = new solanaWeb3.PublicKey('TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA');

            const keys = [
                { pubkey: sourceAccount, isSigner: false, isWritable: true },
                { pubkey: destAccount, isSigner: false, isWritable: true },
                { pubkey: new solanaWeb3.PublicKey(publicKey), isSigner: true, isWritable: false }
            ];

            // Manually encode SPL Token Transfer instruction using native Uint8Array
            // Format: [instruction_type: u8, amount: u64 little-endian]
            const data = new Uint8Array(9);
            data[0] = 3; // 3 = Transfer instruction

            // Write u64 amount in little-endian using DataView
            const amountBigInt = BigInt(amount);
            const view = new DataView(data.buffer);
            view.setBigUint64(1, amountBigInt, true); // true = little-endian

            const transferInstruction = new solanaWeb3.TransactionInstruction({
                keys,
                programId: TOKEN_PROGRAM_ID,
                data
            });

            const transaction = new solanaWeb3.Transaction().add(transferInstruction);

            const { blockhash } = await connection.getLatestBlockhash();
            transaction.recentBlockhash = blockhash;
            transaction.feePayer = new solanaWeb3.PublicKey(publicKey);

            const signed = await provider.signAndSendTransaction(transaction);

            showStatus('Verifying payment...', 'success');
            await connection.confirmTransaction(signed.signature);
            await registerPurchase(slug, targetUrl, signed.signature, currency);
        }

    } catch (error) {
        console.error('Purchase failed:', error);
        showStatus('Payment failed: ' + error.message, 'error');
    }
}

// Register purchase on backend
async function registerPurchase(slug, targetUrl, signature, currency) {
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'purchase',
                slug,
                targetUrl,
                wallet: publicKey,
                signature,
                currency
            })
        });

        const data = await response.json();

        if (data.success) {
            // Show thank you modal with link
            const linkUrl = `https://${slug}.gudtek.lol`;
            const message = `Success! Your domain is now live at:<br><br><strong>${slug}.gudtek.lol</strong>`;

            modalSuccess(message, linkUrl, `✓ VISIT YOUR DOMAIN →`);

            document.getElementById('slugInput').value = '';
            document.getElementById('urlInput').value = '';
            await loadDomains();
            await loadMyDomains();
        } else {
            modalAlert('Error: ' + data.error, 'PURCHASE FAILED');
        }
    } catch (error) {
        console.error('Registration failed:', error);
        showStatus('Failed to register purchase', 'error');
    }
}

// Show status message (use modals for errors, inline for success)
function showStatus(message, type = 'success') {
    if (type === 'error') {
        modalAlert(message, 'ERROR');
    } else {
        // Keep inline message for success/info
        const statusMsg = document.getElementById('statusMsg');
        statusMsg.textContent = message;
        statusMsg.className = 'status-msg ' + type;
        statusMsg.style.display = 'block';

        setTimeout(() => {
            statusMsg.style.display = 'none';
        }, 5000);
    }
}

// Utility functions
function truncateAddress(address) {
    return address.slice(0, 4) + '...' + address.slice(-4);
}

function truncateUrl(url) {
    return url.length > 50 ? url.slice(0, 50) + '...' : url;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Stripe payment function
async function purchaseWithStripe() {
    const slug = document.getElementById('slugInput').value.trim();
    const targetUrl = document.getElementById('urlInput').value.trim();

    if (!slug || !targetUrl) {
        showMessage('Please fill in both domain name and target URL', 'error');
        return;
    }

    // Validate URL
    try {
        new URL(targetUrl);
    } catch (e) {
        showMessage('Please enter a valid URL (include http:// or https://)', 'error');
        return;
    }

    showMessage('Redirecting to Stripe checkout...', 'success');

    try {
        const response = await fetch('stripe_payment.php?action=create_checkout', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                slug: slug,
                target_url: targetUrl
            })
        });

        const data = await response.json();

        if (data.success && data.url) {
            // Redirect to Stripe Checkout
            window.location.href = data.url;
        } else {
            showMessage('Error: ' + (data.error || 'Failed to create checkout session'), 'error');
        }
    } catch (error) {
        console.error('Stripe error:', error);
        showMessage('Error creating checkout session', 'error');
    }
}

// Check for Stripe payment success on page load
window.addEventListener('load', async function() {
    const urlParams = new URLSearchParams(window.location.search);
    const payment = urlParams.get('payment');
    const sessionId = urlParams.get('session_id');

    if (payment === 'success' && sessionId) {
        // Verify payment with backend
        try {
            const response = await fetch(`stripe_payment.php?action=verify_payment&session_id=${sessionId}`);
            const data = await response.json();

            if (data.success && data.paid) {
                showMessage(`✓ Payment successful! Your redirect ${data.slug}.gudtek.lol is now live!`, 'success');
                // Clear URL parameters
                window.history.replaceState({}, document.title, window.location.pathname);
                // Refresh redirects list
                if (typeof loadMyDomains === 'function') {
                    loadMyDomains();
                }
            }
        } catch (error) {
            console.error('Payment verification error:', error);
        }
    } else if (payment === 'cancelled') {
        showMessage('Payment was cancelled', 'error');
        // Clear URL parameters
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

// Contact message functionality
document.addEventListener('DOMContentLoaded', function() {
    const sendBtn = document.getElementById('sendMessageBtn');
    if (sendBtn) {
        sendBtn.addEventListener('click', sendContactMessage);
    }
});

async function sendContactMessage() {
    const messageInput = document.getElementById('contactMessage');
    const statusDiv = document.getElementById('messageStatus');
    const message = messageInput.value.trim();

    if (!message) {
        statusDiv.textContent = 'Please enter a message';
        statusDiv.style.display = 'block';
        statusDiv.style.color = '#f00';
        statusDiv.style.borderColor = '#f00';
        setTimeout(() => statusDiv.style.display = 'none', 3000);
        return;
    }

    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'sendMessage',
                message: message,
                wallet: publicKey || 'anonymous'
            })
        });

        const data = await response.json();

        if (data.success) {
            statusDiv.textContent = '✓ Message sent! Thank you for your feedback.';
            statusDiv.style.display = 'block';
            statusDiv.style.color = 'var(--crt-green)';
            statusDiv.style.borderColor = 'var(--crt-green)';
            messageInput.value = '';
            setTimeout(() => statusDiv.style.display = 'none', 5000);
        } else {
            statusDiv.textContent = 'Error: ' + (data.error || 'Failed to send message');
            statusDiv.style.display = 'block';
            statusDiv.style.color = '#f00';
            statusDiv.style.borderColor = '#f00';
            setTimeout(() => statusDiv.style.display = 'none', 5000);
        }
    } catch (error) {
        console.error('Error sending message:', error);
        statusDiv.textContent = 'Error: Failed to send message';
        statusDiv.style.display = 'block';
        statusDiv.style.color = '#f00';
        statusDiv.style.borderColor = '#f00';
        setTimeout(() => statusDiv.style.display = 'none', 5000);
    }
}
