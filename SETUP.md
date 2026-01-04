# gudtek.lol/is - Solana URL Shortener

A lightweight, decentralized URL shortener built on PHP/Apache with Phantom wallet integration.

## Features

- 🔗 Custom subdomain redirects (slug.gudtek.lol)
- 💰 Payment via Solana (SOL or custom SPL tokens)
- 🎨 Green CRT aesthetic UI
- 👛 Phantom wallet integration
- 🛠️ Admin panel for configuration and moderation
- 📊 Transaction tracking
- 💾 SQLite database (no external DB required)

## Files Structure

```
/var/www/gudtek.lol/is/
├── index.php           # Main frontend + redirect handler
├── app.js              # Phantom wallet integration & UI logic
├── api.php             # Public API endpoints
├── admin.php           # Admin panel UI
├── admin_api.php       # Admin API endpoints
├── init_db.php         # Database initialization script
├── data.db             # SQLite database (auto-created)
├── wildcard-gudtek.conf # Apache VirtualHost config
└── SETUP.md            # This file
```

## Setup Instructions

### 1. Database is Already Initialized ✓

The database has been created with:
- `redirects` table for storing URL mappings
- `config` table for system settings
- `transactions` table for payment history

### 2. Configure Apache Wildcard Subdomains

**Option A: If you want this service to handle ALL *.gudtek.lol subdomains:**

```bash
# Copy the config to sites-available
sudo cp wildcard-gudtek.conf /etc/apache2/sites-available/

# IMPORTANT: This will conflict with your existing gudtek.lol config
# You may need to disable the current one or merge the configurations

# Enable the new config
sudo a2ensite wildcard-gudtek.conf

# Enable required Apache modules
sudo a2enmod rewrite headers

# Test configuration
sudo apache2ctl configtest

# Reload Apache
sudo systemctl reload apache2
```

**Option B: Create a separate subdomain (Recommended to avoid conflicts):**

Instead of using *.gudtek.lol, consider using a specific subdomain like `is.gudtek.lol` or `s.gudtek.lol`:

1. Update DNS to point your chosen subdomain to this server
2. Get SSL certificate: `sudo certbot --apache -d is.gudtek.lol -d *.is.gudtek.lol`
3. Update the VirtualHost config accordingly

**Current Situation:**
- Your main `gudtek.lol` config points to `/var/www/gudtek.lol`
- This service is in `/var/www/gudtek.lol/is`
- You'll need to decide how to route traffic

### 3. SSL Certificate (Required for Phantom Wallet)

Phantom wallet requires HTTPS. For wildcard subdomains:

```bash
# Install certbot DNS plugin (for wildcard certs)
sudo apt install python3-certbot-dns-cloudflare  # or your DNS provider

# Get wildcard certificate
sudo certbot --apache -d gudtek.lol -d *.gudtek.lol --dns-cloudflare

# Or for manual DNS verification
sudo certbot --apache -d gudtek.lol -d *.gudtek.lol --manual --preferred-challenges dns
```

### 4. File Permissions

```bash
# Make sure Apache can write to the database
sudo chown www-data:www-data /var/www/gudtek.lol/is/data.db
sudo chmod 664 /var/www/gudtek.lol/is/data.db
```

### 5. Admin Panel Access

Access the admin panel at: `https://gudtek.lol/is/admin.php`

**Admin wallet:** `819ywRTzmw3Gfei4UgBbmw3FaNRVaPu8Npmz4bcRZFA6`

Only this wallet can:
- Configure SOL and token pricing
- Set custom token mint address
- Moderate/delete redirects
- View transaction history

### 6. Configure Your Token (Optional)

1. Go to `https://gudtek.lol/is/admin.php`
2. Connect with admin wallet
3. Enter your SPL token mint address
4. Set token pricing

### 7. Testing

**Test the main page:**
```
https://gudtek.lol/is/
```

**Test redirect (after creating one):**
```
https://yourslug.gudtek.lol
```

**Test admin panel:**
```
https://gudtek.lol/is/admin.php
```

## Usage

### For Users:

1. Visit the site and connect Phantom wallet
2. Enter desired slug (e.g., "moon" for moon.gudtek.lol)
3. Enter target URL
4. Pay with SOL or token
5. Your redirect is live instantly!

### For Admin:

1. Access admin.php with the admin wallet
2. Configure pricing and token settings
3. Monitor transactions and redirects
4. Delete spam/malicious redirects

## Important Notes

⚠️ **Apache Configuration Conflict**

Your existing `gudtek.lol-le-ssl.conf` serves the main domain. You need to decide:

1. **Keep main site separate**: Use a specific subdomain like `is.gudtek.lol` or `s.gudtek.lol` for this service
2. **Integrate**: Modify the main config to route certain paths/subdomains to this service
3. **Replace**: Make this the primary handler (not recommended if you have other content)

⚠️ **Security Considerations**

- Validate all URLs to prevent open redirects to malicious sites
- Consider implementing rate limiting
- Monitor for abuse (phishing, malware distribution)
- The admin wallet check is simple - consider adding signature verification

⚠️ **Payment Processing**

The current implementation uses direct wallet transfers. For production:
- Implement proper transaction verification
- Add confirmation waiting
- Handle failed transactions
- Consider using a payment processor service

## Customization

### Change CRT Colors

Edit the CSS variables in `index.php`:
```css
:root {
    --crt-green: #0f0;  /* Change to any color */
    --crt-dark: #001a00;
    --crt-glow: rgba(0, 255, 0, 0.3);
}
```

### Add Sponsors

Edit the sponsor boxes in `index.php` around line 400:
```html
<div class="sponsor-box">YOUR SPONSOR</div>
```

### Adjust Pricing

Use the admin panel or directly update the database:
```bash
sqlite3 data.db "UPDATE config SET value = '0.05' WHERE key = 'price_sol';"
```

## Troubleshooting

**Redirects not working?**
- Check Apache rewrite module is enabled
- Verify DNS points to server
- Check database for the redirect entry

**Wallet won't connect?**
- Must use HTTPS
- Check browser console for errors
- Verify Phantom extension is installed

**Admin panel access denied?**
- Make sure you're using the exact admin wallet address
- Check browser console for connection issues

**Database errors?**
- Check file permissions on data.db
- Ensure SQLite3 PHP extension is installed

## Support

Built with ❤️ and green phosphor.
