# Stripe Payment Setup

This application supports Stripe payments in addition to Solana (SOL) and SPL tokens.

## Installation

1. **Install Stripe PHP Library**

```bash
composer install
```

This will install the Stripe PHP SDK from `composer.json`.

2. **Configure Stripe API Key**

Copy the sample config file:

```bash
cp stripe_config.sample.php stripe_config.php
```

Edit `stripe_config.php` and add your Stripe secret key:

```php
define('STRIPE_SECRET_KEY', 'sk_live_your_actual_stripe_key_here');
```

Get your API key from: https://dashboard.stripe.com/apikeys

3. **Set File Permissions**

```bash
chmod 600 stripe_config.php  # Protect the config file
```

## Pricing

Stripe prices are automatically converted from the SOL price configured in the admin panel:

- Minimum charge: **$0.50 USD** (Stripe minimum)
- If SOL price < $0.50: Stripe charges $0.50
- If SOL price >= $0.50: Stripe charges the same numeric value in USD

Example:
- Admin sets: 0.025 SOL
- Stripe charges: $0.50 (minimum)

## Webhook Configuration (Optional but Recommended)

For production use, set up a Stripe webhook to handle payment confirmations automatically:

1. Go to https://dashboard.stripe.com/webhooks
2. Add endpoint: `https://yourdomain.com/stripe_payment.php?action=webhook`
3. Select events: `checkout.session.completed`
4. Get webhook signing secret
5. Add to `stripe_config.php`:

```php
define('STRIPE_WEBHOOK_SECRET', 'whsec_your_webhook_secret');
```

## Security Notes

- **Never commit `stripe_config.php` to version control**
- The file is already in `.gitignore`
- Use environment variables in production if possible
- Restrict file permissions: `chmod 600 stripe_config.php`

## Testing

Use Stripe test keys for development:
- Test Secret Key: `sk_test_...`
- Test Card: 4242 4242 4242 4242 (any future date, any CVC)

More test cards: https://stripe.com/docs/testing

## Payment Flow

1. User clicks "PAY WITH CARD 💳"
2. Redirects to Stripe Checkout
3. After payment, returns to site with session ID
4. Backend verifies payment
5. Creates redirect in database
6. User sees success message

The redirect is live immediately after successful payment.
