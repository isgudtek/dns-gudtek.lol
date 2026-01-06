# Wildcard SSL Setup for Programmatic Subdomains

## The Goal
When a user purchases `moon`, it should automatically work at `https://moon.gudtek.lol` with valid SSL - **without any manual intervention per subdomain**.

## How It Works

### 1. Wildcard DNS (Already Done?)
First, verify you have wildcard DNS configured:

```bash
# Test if wildcard DNS works
dig random123.gudtek.lol
dig another456.gudtek.lol
```

Both should resolve to your server IP. If not, add this to your DNS provider:
```
Type: A
Name: *
Value: YOUR_SERVER_IP
TTL: 300
```

### 2. Wildcard SSL Certificate (THE KEY STEP)

You need ONE wildcard certificate that covers `*.gudtek.lol`. This single cert will work for ALL subdomains automatically.

**Option A: Certbot with DNS Validation (Recommended)**

Wildcard certs REQUIRE DNS validation (not HTTP validation). You need a Certbot DNS plugin for your provider:

**For Cloudflare:**
```bash
# Install plugin
sudo apt install python3-certbot-dns-cloudflare

# Create API token file
sudo nano /root/.secrets/cloudflare.ini
```

Add to cloudflare.ini:
```ini
dns_cloudflare_api_token = YOUR_CLOUDFLARE_API_TOKEN
```

```bash
# Secure the file
sudo chmod 600 /root/.secrets/cloudflare.ini

# Get wildcard cert
sudo certbot certonly \
  --dns-cloudflare \
  --dns-cloudflare-credentials /root/.secrets/cloudflare.ini \
  -d gudtek.lol \
  -d *.gudtek.lol
```

**For Other DNS Providers:**
- DigitalOcean: `python3-certbot-dns-digitalocean`
- Route53: `python3-certbot-dns-route53`
- Namecheap, etc: Search for `certbot dns [provider]`

**Option B: Manual DNS Validation**
```bash
sudo certbot certonly --manual --preferred-challenges dns -d gudtek.lol -d *.gudtek.lol
```
Certbot will ask you to create TXT records. Add them to your DNS, wait 2-3 minutes, then continue.

### 3. Apache VirtualHost Configuration

Create a new config that catches ALL subdomains:

```bash
sudo nano /etc/apache2/sites-available/wildcard-gudtek-ssl.conf
```

Add this configuration:

```apache
<VirtualHost *:443>
    ServerName gudtek.lol
    ServerAlias *.gudtek.lol

    DocumentRoot /var/www/gudtek.lol/is

    <Directory /var/www/gudtek.lol/is>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted

        # Redirect logic handled by PHP
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^ index.php [L]
    </Directory>

    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/gudtek.lol/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/gudtek.lol/privkey.pem
    Include /etc/letsencrypt/options-ssl-apache.conf

    # Logs
    ErrorLog ${APACHE_LOG_DIR}/wildcard_gudtek_error.log
    CustomLog ${APACHE_LOG_DIR}/wildcard_gudtek_access.log combined

    # CORS for Solana
    <IfModule mod_headers.c>
        Header set Access-Control-Allow-Origin "*"
    </IfModule>
</VirtualHost>

# HTTP to HTTPS redirect
<VirtualHost *:80>
    ServerName gudtek.lol
    ServerAlias *.gudtek.lol

    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}$1 [R=301,L]
</VirtualHost>
```

### 4. Enable and Test

```bash
# Enable required modules
sudo a2enmod ssl rewrite headers

# Enable the site
sudo a2ensite wildcard-gudtek-ssl.conf

# Test configuration
sudo apache2ctl configtest

# Reload Apache
sudo systemctl reload apache2
```

### 5. Verify It Works

Test any random subdomain:
```bash
curl -I https://test123.gudtek.lol
curl -I https://random456.gudtek.lol
```

Both should:
- Return 200 or redirect properly
- Show valid SSL certificate
- No certificate warnings

## Important Notes

### Conflict Resolution
⚠️ You currently have `gudtek.lol-le-ssl.conf` serving the main domain. You need to:

**Option 1: Separate Services**
- Keep main `gudtek.lol` for your existing site
- Use `s.gudtek.lol` or `go.gudtek.lol` for the shortener
- Get cert for `*.s.gudtek.lol` or `*.go.gudtek.lol`
- Users buy `moon.s.gudtek.lol` instead

**Option 2: Merge Configs**
- Modify existing config to add ServerAlias *.gudtek.lol
- Route requests based on subdomain to different apps
- More complex but cleaner URLs

**Option 3: Replace Main Site**
- Disable old config
- Use this as primary handler
- Main `gudtek.lol` shows the shortener service

### Certificate Renewal

Wildcard certs auto-renew with certbot:
```bash
# Test renewal
sudo certbot renew --dry-run

# Certbot creates a cron job automatically
# Check it's there:
sudo systemctl status certbot.timer
```

### Testing Locally

Can't test wildcard SSL locally easily, but you can test the redirect logic:
```bash
# Simulate subdomain request
curl -H "Host: moon.gudtek.lol" http://localhost/is/
```

## FAQ

**Q: Do I need to create a certificate for each new subdomain?**
No! That's the beauty of wildcard certs. ONE cert covers infinite subdomains.

**Q: Will existing purchased slugs automatically get HTTPS?**
Yes, instantly. Once the wildcard cert is installed, all subdomains work.

**Q: What about 4th level domains (x.y.gudtek.lol)?**
`*.gudtek.lol` only covers one level. For `*.is.gudtek.lol`, you'd need a separate wildcard cert.

**Q: Does this cost money?**
No! Let's Encrypt wildcard certs are 100% free.

**Q: How long does setup take?**
- DNS validation: 5-10 minutes
- Cert issuance: 1-2 minutes
- Apache config: 2 minutes
- **Total: ~15 minutes**

## Current Status Check

Run these to see what you have:

```bash
# Check DNS
dig test.gudtek.lol

# Check existing certs
sudo certbot certificates

# Check Apache configs
apache2ctl -S | grep gudtek
```
