# Solana URL Shortener

A lightweight, decentralized URL shortener with Phantom wallet integration, enabling paid custom subdomain redirects on the Solana blockchain.

## Features

- Custom subdomain redirects (e.g., `slug.yourdomain.com`)
- Solana payment integration (SOL or custom SPL tokens)
- Phantom wallet support
- Admin panel for configuration and moderation
- Transaction tracking and history
- SQLite database (no external database required)
- Retro CRT-style UI theme

## Prerequisites

- **Web Server**: Apache 2.4+ with mod_rewrite and mod_headers
- **PHP**: 7.4+ with SQLite3 extension
- **SSL Certificate**: Required for Phantom wallet (supports HTTPS only)
- **Domain**: Wildcard DNS or subdomain setup
- **Solana Wallet**: Admin wallet address for system configuration

## Installation

### 1. Clone Repository

```bash
cd /var/www
git clone https://github.com/yourusername/dns-gudtek.lol.git
cd dns-gudtek.lol
```

Or extract to your web directory (e.g., `/var/www/html/shortener`).

### 2. Initialize Database

Run the database initialization script:

```bash
php init_db.php
```

This creates `data.db` with the following tables:
- `redirects` - URL mappings and slug data
- `config` - System settings and pricing
- `transactions` - Payment history
- `messages` - User messages/notifications

### 3. Set File Permissions

Ensure Apache can read/write to the database:

```bash
sudo chown www-data:www-data data.db
sudo chmod 664 data.db

# If using a different web server user (e.g., nginx):
# sudo chown nginx:nginx data.db
```

### 4. Configure DNS

Choose one of two approaches:

**Option A: Wildcard Subdomain (Recommended)**

Add a wildcard DNS record:
```
A    *    your.server.ip.address
```

This allows any subdomain (e.g., `test.yourdomain.com`, `moon.yourdomain.com`) to route to your server.

**Option B: Specific Subdomain**

Point a specific subdomain to your server:
```
A    short       your.server.ip.address
A    *.short     your.server.ip.address
```

Then access via `short.yourdomain.com` with redirects at `slug.short.yourdomain.com`.

### 5. Configure Apache

#### Edit VirtualHost Configuration

Update `wildcard-gudtek.conf` or create a new config file:

```apache
<VirtualHost *:443>
    ServerName short.yourdomain.com
    ServerAlias *.yourdomain.com

    DocumentRoot /var/www/dns-gudtek.lol

    <Directory /var/www/dns-gudtek.lol>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted

        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^ index.php [L,QSA]
    </Directory>

    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/yourdomain.com/privkey.pem
    Include /etc/letsencrypt/options-ssl-apache.conf

    # CORS for Solana/Phantom
    <IfModule mod_headers.c>
        Header set Access-Control-Allow-Origin "*"
    </IfModule>

    ErrorLog ${APACHE_LOG_DIR}/shortener_error.log
    CustomLog ${APACHE_LOG_DIR}/shortener_access.log combined
</VirtualHost>

# HTTP to HTTPS redirect
<VirtualHost *:80>
    ServerName short.yourdomain.com
    ServerAlias *.yourdomain.com

    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}$1 [R=301,L]
</VirtualHost>
```

#### Enable Configuration

```bash
# Copy config to sites-available
sudo cp wildcard-gudtek.conf /etc/apache2/sites-available/yourdomain-shortener.conf

# Enable required modules
sudo a2enmod rewrite headers ssl

# Enable the site
sudo a2ensite yourdomain-shortener.conf

# Test configuration
sudo apache2ctl configtest

# Reload Apache
sudo systemctl reload apache2
```

### 6. Obtain SSL Certificate

Phantom wallet requires HTTPS. Obtain a wildcard SSL certificate:

```bash
# Using Certbot with DNS validation (recommended for wildcards)
sudo certbot certonly --manual --preferred-challenges dns \
  -d yourdomain.com -d *.yourdomain.com

# Or using Certbot with Apache (for specific subdomains)
sudo certbot --apache -d short.yourdomain.com
```

Follow the prompts to complete DNS verification.

### 7. Configure Admin Wallet

Access the admin panel to set your admin wallet address:

1. Open `https://short.yourdomain.com/admin.php`
2. Connect your Phantom wallet
3. The first connected wallet becomes the admin (or manually set in database)

**Manual Configuration** (if needed):

```bash
sqlite3 data.db "UPDATE config SET value = 'YOUR_WALLET_ADDRESS' WHERE key = 'admin_wallet';"
```

### 8. Configure Pricing

Set SOL and token pricing through the admin panel or directly:

```bash
# Set SOL price (e.g., 0.025 SOL)
sqlite3 data.db "UPDATE config SET value = '0.025' WHERE key = 'price_sol';"

# Set token price (e.g., 10000 tokens)
sqlite3 data.db "UPDATE config SET value = '10000' WHERE key = 'price_token';"

# Set SPL token mint address (optional)
sqlite3 data.db "UPDATE config SET value = 'YOUR_TOKEN_MINT_ADDRESS' WHERE key = 'token_mint';"
```

## Usage

### For End Users

1. Navigate to `https://short.yourdomain.com`
2. Connect Phantom wallet
3. Enter desired slug (e.g., "moon" → `moon.yourdomain.com`)
4. Enter target URL to redirect to
5. Pay with SOL or custom token
6. Redirect is active immediately

### For Administrators

Access the admin panel at `https://short.yourdomain.com/admin.php` to:
- Configure pricing and accepted tokens
- View all active redirects
- Delete spam or malicious redirects
- Monitor transaction history
- Update system settings

## Customization

### Change UI Theme Colors

Edit CSS variables in `index.php` (around line 50):

```css
:root {
    --crt-green: #0f0;              /* Primary color */
    --crt-dark: #001a00;            /* Background */
    --crt-glow: rgba(0, 255, 0, 0.3); /* Glow effect */
}
```

### Modify Pricing Dynamically

Use the admin panel or update the database:

```bash
sqlite3 data.db "UPDATE config SET value = '0.05' WHERE key = 'price_sol';"
```

### Add Sponsor Content

Edit sponsor sections in `index.php` (search for "sponsor-box"):

```html
<div class="sponsor-box">
    Your sponsor content here
</div>
```

## API Endpoints

### Public API (`api.php`)

- **POST /api.php?action=create_redirect** - Create new redirect (requires payment)
- **GET /api.php?action=check_slug&slug=xxx** - Check slug availability
- **POST /api.php?action=record_transaction** - Record blockchain transaction

### Admin API (`admin_api.php`)

- **GET /admin_api.php?action=get_redirects** - List all redirects
- **POST /admin_api.php?action=delete_redirect** - Delete a redirect
- **GET /admin_api.php?action=get_stats** - Get system statistics
- **POST /admin_api.php?action=update_config** - Update configuration

All admin endpoints require admin wallet authentication.

## Security Considerations

### Production Deployment Checklist

- [ ] Implement URL validation to prevent malicious redirects
- [ ] Add rate limiting to prevent abuse
- [ ] Implement transaction verification (currently not validated)
- [ ] Add CAPTCHA or proof-of-work for spam prevention
- [ ] Enable admin signature verification
- [ ] Set up monitoring for malicious content
- [ ] Configure firewall rules
- [ ] Regular database backups
- [ ] Implement logging and audit trails

### Current Limitations

- **No transaction verification**: Payments are not verified on-chain
- **Simple admin auth**: Only wallet address matching (no signature verification)
- **No rate limiting**: Vulnerable to spam/abuse
- **No URL validation**: Could be used for phishing/malware distribution

**This is a proof-of-concept implementation. Production use requires additional security hardening.**

## Troubleshooting

### Redirects Not Working

- Verify Apache `mod_rewrite` is enabled: `sudo a2enmod rewrite`
- Check DNS propagation: `dig slug.yourdomain.com`
- Review Apache error logs: `tail -f /var/log/apache2/shortener_error.log`
- Verify database entry exists: `sqlite3 data.db "SELECT * FROM redirects;"`

### Phantom Wallet Won't Connect

- Ensure site is served over HTTPS (Phantom requires SSL)
- Check browser console for errors (F12)
- Verify Phantom extension is installed and unlocked
- Try clearing browser cache/cookies

### Admin Panel Access Denied

- Confirm you're connecting with the admin wallet address
- Check `config` table: `sqlite3 data.db "SELECT * FROM config WHERE key = 'admin_wallet';"`
- Review browser console for connection issues

### Database Permission Errors

- Verify file ownership: `ls -l data.db`
- Should be owned by web server user (www-data, nginx, etc.)
- Check permissions: `chmod 664 data.db`

### SSL Certificate Issues

- Verify certificate paths in Apache config
- Check certificate expiration: `openssl x509 -in /path/to/cert.pem -noout -dates`
- Renew certificates: `sudo certbot renew`

## File Structure

```
.
├── index.php              # Main frontend + redirect handler
├── app.js                 # Phantom wallet integration & client-side logic
├── api.php                # Public API endpoints
├── admin.php              # Admin panel UI
├── admin_api.php          # Admin API endpoints
├── init_db.php            # Database initialization script
├── data.db                # SQLite database (created after init)
├── wildcard-gudtek.conf   # Example Apache configuration
├── distro/                # Distribution system (optional)
└── SETUP.md               # This file
```

## Requirements

### PHP Extensions

```bash
# Verify required extensions are installed
php -m | grep -E "sqlite3|pdo_sqlite|json|curl"

# Install if missing (Ubuntu/Debian)
sudo apt install php-sqlite3 php-curl php-json
```

### Apache Modules

```bash
# Verify required modules
apache2ctl -M | grep -E "rewrite|headers|ssl"

# Enable if missing
sudo a2enmod rewrite headers ssl
sudo systemctl restart apache2
```

## License

This project is provided as-is for educational and commercial use. Review code before deploying to production.

## Contributing

Contributions welcome! Please submit pull requests or open issues for bugs and feature requests.

## Support

For issues and questions, please open a GitHub issue or refer to the troubleshooting section above.
