# Distribution Flywheel System

Automated treasury distribution mechanism that distributes a percentage of the admin wallet balance to token holders at regular intervals.

## Features

- **Real-time Dashboard**: Live updates of treasury balance, countdown timer, and holder stats
- **Automated Distribution**: Configurable intervals and percentages
- **Manual Execution**: Admin can trigger distributions at any time
- **Distribution History**: Track all past distributions
- **Token Holder Analytics**: View top holders and their allocations

## Access

Visit: `https://is.gudtek.lol/distro/`

## Configuration

### Admin Access Required
- Connect with admin wallet: `819ywRTzmw3Gfei4UgBbmw3FaNRVaPu8Npmz4bcRZFA6`
- Only the admin wallet can execute distributions and change settings

### Settings
- **Distribution Interval**: Time between automatic distributions (in seconds)
  - 86400 = 24 hours
  - 3600 = 1 hour
  - 600 = 10 minutes
- **Distribution Percentage**: 1-100% of treasury balance to distribute
- **Enable/Disable**: Toggle automatic distributions on/off

## How It Works

1. **Treasury**: Admin wallet balance is monitored in real-time
2. **Calculation**: Distribution amount = Treasury × Percentage
3. **Allocation**: Amount is split proportionally among token holders
4. **Execution**: SOL is sent to each holder based on their token ownership %

## Distribution Formula

For each holder:
```
holder_amount = (total_distribution × holder_tokens) / total_supply
```

## Important Notes

### ⚠️ SECURITY NOTICE
- Current implementation **simulates** distributions (doesn't actually send transactions)
- To enable real distributions, you need to:
  1. Add admin wallet private key to secure storage (environment variable or key vault)
  2. Implement transaction signing logic
  3. Add batch transaction sending

### Minimum Distribution
- Holders receive minimum 0.001 SOL
- Smaller amounts are skipped to avoid dust

### Files
- `index.php` - Main GUI dashboard
- `api.php` - Backend API for data and execution
- `app.js` - Real-time JavaScript updates
- `config.json` - Configuration storage
- `distribution_history.json` - Distribution records (auto-created)

## API Endpoints

### GET
- `api.php?action=getStatus` - Treasury and distribution status
- `api.php?action=getHolders` - Token holder list
- `api.php?action=getHistory` - Distribution history

### POST (Admin Only)
- `action=executeDistribution` - Execute distribution now
- `action=updateConfig` - Update settings

## Real-time Updates

- Status refreshes every 5 seconds
- Countdown updates every second
- All data syncs automatically

## Testing

Current setup uses:
- **Admin Wallet**: 819ywRTzmw3Gfei4UgBbmw3FaNRVaPu8Npmz4bcRZFA6
- **Token Mint**: From main config (holders.php)
- **Helius API**: Mainnet RPC access

## Future Enhancements

- Actual transaction execution (requires private key)
- Email/Discord notifications on distribution
- Multi-signature support
- Scheduled distributions at specific times
- CSV export of distributions
- Whale protection (max % per holder)
