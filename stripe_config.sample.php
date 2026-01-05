<?php
/**
 * Stripe Payment Configuration
 *
 * Copy this file to stripe_config.php and add your Stripe API key
 */

// Stripe API Key - Get from https://dashboard.stripe.com/apikeys
define('STRIPE_SECRET_KEY', 'sk_live_your_stripe_secret_key_here');

// Load Stripe library
require_once __DIR__ . '/vendor/autoload.php';

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
