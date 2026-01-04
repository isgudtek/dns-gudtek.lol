# Distribution Wallet Information

## 🔐 New Wallet Generated (Local to Distro System)

**Public Address:**
```
5Mb8vcPzw2CgEub9NbTUJPQCnoLTRaGiamTkjvETLYzC
```

**Private Key Location:**
```
/var/www/gudtek.lol/is/distro/wallet.json
```

**Private Key (Secret Key Array):**
```json
[181,180,247,81,95,111,79,100,110,145,162,169,83,114,207,174,248,87,14,129,83,12,174,203,129,156,113,113,236,162,20,221,64,180,246,88,215,151,193,17,173,181,134,255,96,134,235,178,162,0,225,135,171,53,230,242,29,219,191,103,165,229,93,73]
```

## 🎯 Purpose

This wallet is **dedicated exclusively** to the distribution flywheel system:
- Holds treasury SOL for distributions
- Automatically distributes to token holders
- Isolated from other applications on this VPS

## ⚙️ Configuration

The system is now configured to:
- ✅ Use the new wallet for all distributions
- ✅ Actually execute real Solana transactions
- ✅ Send SOL to token holders proportionally
- ✅ Track transaction signatures in history

## 💰 How to Fund

Send SOL to this address to fund distributions:
```
5Mb8vcPzw2CgEub9NbTUJPQCnoLTRaGiamTkjvETLYzC
```

You can fund it from:
- Phantom wallet
- Any Solana wallet
- Exchange withdrawal
- The old admin wallet (if you want to transfer funds)

## 🔒 Security

**IMPORTANT:**
- The private key is stored in `wallet.json` with 0600 permissions (owner read/write only)
- Keep this file secure and backed up
- Never share the private key publicly
- Only the web server has access to this file

## 📝 Backup Instructions

To backup your wallet:
```bash
# Copy wallet.json to a secure location
cp /var/www/gudtek.lol/is/distro/wallet.json ~/distro-wallet-backup.json
chmod 600 ~/distro-wallet-backup.json
```

To restore:
```bash
cp ~/distro-wallet-backup.json /var/www/gudtek.lol/is/distro/wallet.json
chmod 600 /var/www/gudtek.lol/is/distro/wallet.json
```

## 🚀 System Status

**Admin Access:**
- Connect with Phantom wallet using address: `5Mb8vcPzw2CgEub9NbTUJPQCnoLTRaGiamTkjvETLYzC`
- Dashboard: https://is.gudtek.lol/distro/

**Distribution Logic:**
- ✅ Real transactions now enabled
- ✅ Uses Node.js script with @solana/web3.js
- ✅ Batched sending (5 transactions at a time)
- ✅ Transaction signatures recorded
- ✅ Automatic rate limiting

## 🔧 Technical Details

**Transaction Script:**
- Location: `/var/www/gudtek.lol/is/distro/execute_distribution.js`
- Uses: @solana/web3.js (installed via npm)
- Method: SystemProgram.transfer
- Network: Mainnet via Helius RPC
- Confirmation: 'confirmed' commitment level

**Files:**
- `wallet.json` - Private key storage
- `execute_distribution.js` - Transaction executor
- `api.php` - Backend API (calls Node.js script)
- `app.js` - Frontend (updated with new admin wallet)

## ⚠️ First Time Setup

Before first distribution:
1. Fund the wallet with SOL
2. Connect with Phantom using the new address
3. Configure distribution settings (interval, percentage)
4. Test with a small amount first

## 📊 Monitoring

Check wallet balance:
```bash
curl -s -X POST https://mainnet.helius-rpc.com/?api-key=13eb1a93-5010-4ae1-9352-d4171b64a57b \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": 1,
    "method": "getBalance",
    "params": ["5Mb8vcPzw2CgEub9NbTUJPQCnoLTRaGiamTkjvETLYzC"]
  }' | jq -r '.result.value / 1000000000'
```

View on Solscan:
https://solscan.io/account/5Mb8vcPzw2CgEub9NbTUJPQCnoLTRaGiamTkjvETLYzC

## 🎉 Ready to Use!

The distribution flywheel is now fully operational with real transaction capabilities!
