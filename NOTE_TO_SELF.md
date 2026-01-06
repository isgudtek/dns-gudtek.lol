# is.gudtek.lol - Project Notes

## Current Status: V1 LIVE (Utility-First Domain Registration)

**Date:** 2026-01-05
**Environment:** /var/www/gudtek.lol/is

---

## What This Is

Solana-based domain registration system for .gudtek.lol subdomains. Users can register short, memorable domains that redirect to any URL. Payment via SOL, tokens, or Stripe.

---

## Important Files & Configs

### Payment Integration
- **Stripe Secret Key**: In `stripe_config.php` (gitignored)
- **Stripe Publishable Key**: NOT needed (server-side checkout)
- **Token Mint**: Loaded from admin config in database
- **Pricing**: Admin configurable SOL base price with length multipliers

### Database
- **File**: `data.db` (SQLite3)
- **Active Domains**: 9 registered
- **Tables**: redirects, config, token holders

### Special Features
- **Length-based pricing multipliers**:
  - 1 char = 10x
  - 2 char = 5x
  - 3 char = 4x
  - 4 char = 3x
  - 5 char = 2x
  - 6+ char = 1x
- **Token payment discount**: 50% off
- **Dynamic SOL price**: CoinGecko API (60s cache)

---

## Recent Changes (Session Summary)

### Roadmap Modal Added
- Created `showRoadmapModal()` in app.js
- Explains 5-phase vision:
  1. Alpha Tek (utility - current)
  2. Community building
  3. NFT conversion & airdrop
  4. ME/Tensor verification
  5. Main TLD (.gudtek) for early holders
- "💡 SUGGEST A FEATURE" link in both modals → scrolls to contact form

### Last Domains Section
- Now **dynamic** (fetches from DB)
- Shows **3 rows × 4 domains** (12 total)
- Newest first (ORDER BY id DESC)

### Mail Server
- ✅ Postfix active and working
- Tested: Both `mail` and PHP `mail()` deliver successfully
- Test sent to: ohmannomma@yahoo.it

### Stripe Integration
- ✅ Working after `composer install`
- vendor/ directory excluded from backup but required for operation
- Run `composer install` if missing after restore

---

## V1 vs V2

### V1 (Current - /var/www/gudtek.lol/is/)
- **Status**: LIVE in production
- **Features**: Full payment system (SOL/Token/Stripe)
- **Strategy**: Utility-first, build community before NFTs

### V2 (Development - /var/www/gudtek.lol/isV2/)
- **Status**: NFT implementation paused
- **Contains**:
  - nft/ directory with Merkle tree scripts
  - Fresh wallet: AuU3NZ44FuL6awiCKM7aK3oiFHusZupEFgKBJ1onez25
  - Helius RPC key: 13eb1a93-5010-4ae1-9352-d4171b64a57b
- **When**: NFTs will come later after community growth

---

## Backups

### Version1OK Backup
- **Location**: /root/gudtek-is-Version1OK.tar.gz
- **Database**: /root/gudtek-is-Version1OK-data.db
- **Excluded**: data.db, vendor, node_modules, .git, distro
- **Created**: Before NFT implementation attempt

---

## Security Notes

### Exposed & Regenerated
- ⚠️ Old wallets in distro/ were exposed on GitHub (now gitignored)
- ✅ Fresh wallet generated for NFT work (in isV2)
- ✅ distro/ removed from git tracking

### Gitignore Includes
```
distro/
stripe_config.php
sol_price_cache.json
vendor/
node_modules/
data.db
```

---

## GitHub Repository

- **Repo**: https://github.com/isgudtek/dns-gudtek.lol
- **Branch**: main
- **Last Push**: After cleanup (distro/ removed)

---

## System Resources

### Disk Usage
- **Before cleanup**: 97% full (41GB/45GB)
- **After cleanup**: 61% full (26GB/45GB)
- **Cleaned**: venv_gemma (7GB), .cache (4.8GB), .npm (3.5GB)

### Memory
- **Total RAM**: 1.9GB
- **Note**: OOM killer has triggered MySQL before
- **MySQL**: Auto-restarts, but monitor resource usage

---

## API Endpoints

- `api.php` - Domain registration (SOL/Token payments)
- `admin_api.php` - Admin operations
- `stripe_payment.php` - Stripe checkout & webhooks
- `holders.php` - Token holders list

---

## Contact & Features

- **Contact Form**: Works via PHP mail()
- **Report Abuse**: report@gudtek.lol
- **Feature Requests**: Built into site with contact form

---

## The Tek Vision

"Community first, tech second, hype never."

Start with utility → Build community → Convert to NFTs → Verify on ME/Tensor → Launch .gudtek TLD

Early supporters get upgrade path. This is a proof of concept for sysadmin + crypto + utility tools.

---

## Next Session TODO

- [ ] Monitor payment flows (SOL/Token/Stripe all working)
- [ ] Watch for spam domains (abuse reporting system in place)
- [ ] Consider when community is big enough for NFT conversion
- [ ] Keep isV2 ready for NFT phase when time comes

---

**Remember**: Run `composer install` if vendor/ is missing after any restore!
