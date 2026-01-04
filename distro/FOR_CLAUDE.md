# Automated Royalty Distribution System - Project Documentation

## System Overview

This is an automated cryptocurrency distribution system that:
1. Collects PumpPortal creator fees (0.05% per trade) from Pump.fun tokens
2. Distributes collected fees to token holders proportionally (50%)
3. Sends remainder to admin wallet (50%)
4. Runs autonomously via Python daemon on 60-second cycles
5. Provides real-time UI with Matrix cyberpunk aesthetic

## Current Configuration

### Wallets
- **Treasury Wallet**: `DmXxdBDNxe7Rrq1SReV6FQcG1cswrL2MHxYQPf4J9XdS`
- **Admin Wallet** (receives 50% remainder): `819ywRTzmw3Gfei4UgBbmw3FaNRVaPu8Npmz4bcRZFA6`
- **Current Token**: `AsvGRzwD2Msz46v1fV9pCgfKwofWDKXVJH55XxJVpump`

### PumpPortal Integration
- API Key stored in: `/var/www/gudtek.lol/is/distro/pumportal_config.json`
- Uses Lightning API endpoint: `https://pumpportal.fun/api/trade`
- Action: `collectCreatorFee` with pool: `pump`

### Distribution Settings
- **Cycle Interval**: 60 seconds
- **Distribution Percentage**: 50% to holders, 50% to admin
- **Minimum Balance**: System won't distribute if balance too low
- **Excludes**: Largest holder (LP) from distributions

## System Architecture

### Core Components

#### 1. Backend API (`api.php`)
**Location**: `/var/www/gudtek.lol/is/distro/api.php`

**Key Endpoints**:
- `collectRoyalties` - Executes royalty collection via collect_royalties.php
- `getRoyaltyStats` - Returns royalty_stats.json content
- `getDaemonStatus` - Returns daemon heartbeat
- `calculateDistribution` - Prepares holder distribution
- `executeDistribution` - Triggers Node.js distribution script
- `startTimer` / `stopTimer` / `resetTimer` - Timer controls
- `getConfig` / `updateConfig` - Configuration management

**Admin Wallets Array**:
```php
$ADMIN_WALLETS = [
    'DmXxdBDNxe7Rrq1SReV6FQcG1cswrL2MHxYQPf4J9XdS', // Treasury
    '5Mb8vcPzw2CgEub9NbTUJPQCnoLTRaGiamTkjvETLYzC', // Old wallet
    '819ywRTzmw3Gfei4UgBbmw3FaNRVaPu8Npmz4bcRZFA6'  // Admin
];
```

#### 2. Royalty Collection (`collect_royalties.php`)
**Location**: `/var/www/gudtek.lol/is/distro/collect_royalties.php`

**Process**:
1. Reads `pumportal_config.json` for API credentials
2. Calls PumpPortal Lightning API with `collectCreatorFee` action
3. Waits 3 seconds for transaction confirmation
4. Queries transaction to get actual amount collected
5. Updates `royalty_stats.json` with totals
6. Logs to `royalty_collection_log.json`

**Important**: Returns success even if 0 collected (allows daemon to continue)

#### 3. Python Daemon (`distribution_daemon.py`)
**Location**: `/var/www/gudtek.lol/is/distro/distribution_daemon.py`

**Cycle Flow**:
```
Countdown → Collect Royalties → Execute Distribution → Restart Timer → Repeat
```

**API URL**: `https://is.gudtek.lol/distro/api.php` (NOT localhost!)

**Functions**:
- `collect_royalties()` - Calls API to collect fees
- `execute_distribution()` - Triggers distribution
- `restart_timer()` - Resets 60s countdown
- `write_log()` - Writes to daemon_activity.json

**Running**:
```bash
# Check if running
ps aux | grep distribution_daemon.py

# Kill if needed
pkill -f distribution_daemon.py

# Start daemon
nohup python3 /var/www/gudtek.lol/is/distro/distribution_daemon.py > /dev/null 2>&1 &
```

#### 4. Distribution Executor (`execute_distribution.js`)
**Location**: `/var/www/gudtek.lol/is/distro/execute_distribution.js`

**Purpose**: Executes Solana transactions using @solana/web3.js

**Process**:
1. Reads `temp_distribution.json` for recipient list
2. Reads `wallet.json` for treasury private key
3. Creates transaction with all transfers
4. Signs and sends to Solana mainnet
5. Returns transaction signature

**Permissions**: Must be executable (`chmod 755`)

#### 5. Frontend (`index.php`, `app.js`, `styles.css`)

**Key UI Elements**:

**Desktop Layout (1200px+)**:
- Left Box: Royalty stats (Last Claimed, Total Collected, Next Drop)
- Center: Token address + countdown GIF + timer + progress bar
- Right Box: Token metadata (image/name/ticker) + holder stats

**Mobile Layout**: Boxes stack vertically

**Features**:
- Click token address to copy (shows "✓ COPIED!" feedback)
- Real-time countdown (1s updates)
- Matrix rain background effect
- Solana logo images (not ◎ symbol)
- Solid black boxes (#000000 opaque)
- One-time metadata fetch using Helius DAS API

**JavaScript Key Functions**:
```javascript
loadStatus() // Every 1s - gets config, countdown, treasury
loadRoyaltyStats() // Every 1s - gets collection stats
loadTokenMetadata() // One-time - fetches from DAS API when token changes
copyTokenAddress() // Clipboard copy with visual feedback
```

## File Structure

### Configuration Files
```
config.json              - Main system config (token, interval, enabled)
wallet.json              - Treasury private key (SECURE - 640 perms)
pumportal_config.json    - PumpPortal API credentials
royalty_stats.json       - Cumulative royalty collection stats
```

### Log Files
```
daemon_activity.json     - Daemon events (countdown, collection, distribution)
royalty_collection_log.json - Detailed royalty collection history
distribution_log.json    - Distribution transaction history
daemon_heartbeat.json    - Daemon health check
```

### Temporary Files
```
temp_distribution.json   - Holder distribution calculations (recreated each cycle)
```

## Important Implementation Details

### 1. Royalty Collection Integration

**Critical**: Royalty collection happens BEFORE every distribution cycle in daemon:

```python
if time_remaining <= 0:
    write_log("⏰ Countdown reached 0 - starting cycle...", 'warning')
    collect_royalties()  # Step 1
    time.sleep(1)
    execute_distribution()  # Step 2
    restart_timer()  # Step 3
```

### 2. Treasury Emptying Logic

After distributing 50% to holders, remainder goes to admin:

```php
$remainingLamports = $balance - $totalDistributedLamports;

if ($remainingLamports > 5000) {
    $distributions[] = [
        'wallet' => '819ywRTzmw3Gfei4UgBbmw3FaNRVaPu8Npmz4bcRZFA6',
        'amount_lamports' => floor($remainingLamports - 5000),
        'is_admin_remainder' => true
    ];
}
```

Leaves only 5000 lamports (~0.000005 SOL) for transaction fees.

### 3. Pump.fun Token Metadata

Standard Metaplex metadata doesn't work for Pump.fun tokens. Must use Helius DAS API:

```javascript
// Correct method
const response = await fetch(`https://mainnet.helius-rpc.com/?api-key=...`, {
    method: 'POST',
    body: JSON.stringify({
        jsonrpc: '2.0',
        method: 'getAsset',
        params: { id: tokenMintAddress }
    })
});

const asset = response.result;
const name = asset.content?.metadata?.name;
const symbol = asset.content?.metadata?.symbol;
const image = asset.content?.links?.image;
```

### 4. Solana Logo Positioning

User specifically requested:
- Use actual Solana logo image (NOT ◎ symbol)
- Push up 5px: `margin-top: -5px`
- Source: `https://uxwing.com/solana-sol-icon/`

### 5. Token Address Display

- Format: `[ TOKEN_ADDRESS ]`
- Size: 0.85rem (smaller than other text)
- Click to copy with green "✓ COPIED!" feedback
- No "TOKEN:" label prefix

## Common Issues & Solutions

### Issue: Daemon not responding
**Solution**: Check API URL is `https://is.gudtek.lol/distro/api.php` NOT localhost

### Issue: Permission denied errors
**Solution**:
```bash
chmod 755 execute_distribution.js distribution_daemon.py
chmod 640 wallet.json pumportal_config.json
chmod 775 /var/www/gudtek.lol/is/distro/
chown root:www-data wallet.json pumportal_config.json
```

### Issue: Distribution failing "Insufficient balance"
**Expected**: System won't distribute if balance below minimum threshold
**Solution**: Wait for trading volume to generate royalties

### Issue: Royalty collection showing 0
**Expected**: Normal if no trading activity on token
**Solution**: Not an error - means no fees accumulated yet

### Issue: Timer not running
**Solution**:
```bash
curl -X POST https://is.gudtek.lol/distro/api.php \
  -H "Content-Type: application/json" \
  -d '{"action":"startTimer","wallet":"DmXxdBDNxe7Rrq1SReV6FQcG1cswrL2MHxYQPf4J9XdS"}'
```

### Issue: Token metadata not showing
**Check**: Console for errors, ensure using DAS API not token-metadata endpoint

## Testing & Verification

### Check System Status
```bash
# View daemon activity
cat /var/www/gudtek.lol/is/distro/daemon_activity.json | jq '.[0:5]'

# View royalty stats
cat /var/www/gudtek.lol/is/distro/royalty_stats.json

# Check if daemon running
ps aux | grep distribution_daemon.py

# Check config
cat /var/www/gudtek.lol/is/distro/config.json
```

### Restart System
```bash
# Stop daemon
pkill -f distribution_daemon.py

# Start daemon
cd /var/www/gudtek.lol/is/distro/
nohup python3 distribution_daemon.py > /dev/null 2>&1 &

# Restart timer
curl -X POST https://is.gudtek.lol/distro/api.php \
  -H "Content-Type: application/json" \
  -d '{"action":"startTimer","wallet":"DmXxdBDNxe7Rrq1SReV6FQcG1cswrL2MHxYQPf4J9XdS"}'
```

## Current System State (as of last session)

**Status**: ✅ Fully operational, waiting for trading volume

- Token: `AsvGRzwD2Msz46v1fV9pCgfKwofWDKXVJH55XxJVpump`
- Treasury Balance: ~0.0024 SOL
- Daemon: Running
- Royalty Collections: Executing every 60s (returning 0 - no trades yet)
- Distribution: Failing due to insufficient balance (expected)
- UI: Fully functional with token metadata displayed

**Expected Behavior**: Once token gets trading volume on Pump.fun:
1. 0.05% creator fees accumulate in PumpPortal
2. Every 60s daemon collects fees to treasury
3. System distributes 50% to holders, 50% to admin
4. Treasury empties each cycle
5. Process repeats automatically

## User Preferences

1. **No unnecessary text**: Keep UI concise, remove labels like "TOKEN:" or "tokens"
2. **Solid black backgrounds**: Boxes must be opaque #000000, no matrix effect overlay
3. **Official Solana branding**: Use actual logo image, not symbols
4. **Responsive design**: Float boxes on desktop, stack on mobile
5. **One-time metadata fetch**: Don't refetch token info every second
6. **Visual feedback**: Show copy confirmation, use emojis in logs
7. **Clean code**: Remove unused features, no over-engineering

## API Keys & Credentials

**SECURITY**: All sensitive data stored in separate config files with proper permissions

- Treasury private key: `wallet.json` (chmod 640)
- PumpPortal API key: `pumportal_config.json` (chmod 640)
- Helius API key: Hardcoded in app.js (public RPC endpoint)

## Next Steps for New Sessions

1. **Check system status**: View logs and verify daemon running
2. **Verify token**: Ensure correct token mint in config.json
3. **Monitor royalties**: Check if any fees accumulated
4. **Test distribution**: Once balance sufficient, verify distribution works
5. **UI adjustments**: Make any requested styling changes

## Development Notes

- Always read files before editing
- Use official Helius DAS API for Pump.fun tokens
- Test on mainnet only (no devnet)
- Treasury should be empty after each successful distribution
- Daemon must use HTTPS API URL, not localhost
- Keep error messages user-friendly in UI
- Log everything to JSON files for debugging

---

**Last Updated**: 2026-01-05
**Project Path**: `/var/www/gudtek.lol/is/distro/`
**Live URL**: `https://is.gudtek.lol/distro/`
