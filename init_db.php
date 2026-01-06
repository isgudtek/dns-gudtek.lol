<?php
// Database initialization script
$db = new SQLite3(__DIR__ . '/data.db');

// Create redirects table
$db->exec('CREATE TABLE IF NOT EXISTS redirects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT UNIQUE NOT NULL,
    target_url TEXT NOT NULL,
    owner_wallet TEXT NOT NULL,
    created_at INTEGER NOT NULL,
    active INTEGER DEFAULT 1
)');

// Create config table
$db->exec('CREATE TABLE IF NOT EXISTS config (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL
)');

// Create transactions table
$db->exec('CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT NOT NULL,
    wallet TEXT NOT NULL,
    amount TEXT NOT NULL,
    currency TEXT NOT NULL,
    tx_signature TEXT,
    created_at INTEGER NOT NULL
)');

// Create messages table for feature requests and contact
$db->exec('CREATE TABLE IF NOT EXISTS messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    message TEXT NOT NULL,
    wallet TEXT,
    created_at INTEGER NOT NULL,
    status TEXT DEFAULT "new"
)');

// Insert default config
$db->exec("INSERT OR IGNORE INTO config (key, value) VALUES ('price_sol', '0.1')");
$db->exec("INSERT OR IGNORE INTO config (key, value) VALUES ('price_token', '100')");
$db->exec("INSERT OR IGNORE INTO config (key, value) VALUES ('token_mint', '')");
$db->exec("INSERT OR IGNORE INTO config (key, value) VALUES ('admin_wallet', '819ywRTzmw3Gfei4UgBbmw3FaNRVaPu8Npmz4bcRZFA6')");

echo "Database initialized successfully\n";
$db->close();
