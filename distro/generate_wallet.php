<?php
// Generate a new Solana keypair

// Generate 64 random bytes for the keypair
$keypair = random_bytes(64);

// Get the first 32 bytes as the secret key
$secretKey = substr($keypair, 0, 32);

// Convert to base58 for easier handling
function base58_encode($data) {
    $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
    $base = strlen($alphabet);

    $num = gmp_import($data);
    $encoded = '';

    while (gmp_cmp($num, 0) > 0) {
        list($num, $remainder) = gmp_div_qr($num, $base);
        $encoded = $alphabet[gmp_intval($remainder)] . $encoded;
    }

    // Add leading zeros
    for ($i = 0; $i < strlen($data) && $data[$i] === "\x00"; $i++) {
        $encoded = $alphabet[0] . $encoded;
    }

    return $encoded;
}

// For Solana, we need to derive the public key from the secret key
// This requires the Ed25519 extension or sodium
if (!function_exists('sodium_crypto_sign_seed_keypair')) {
    die("Error: Sodium extension not available. Install with: apt-get install php-sodium\n");
}

// Generate keypair using libsodium
$keypair = sodium_crypto_sign_seed_keypair($secretKey);
$publicKey = sodium_crypto_sign_publickey($keypair);
$secretKeyFull = sodium_crypto_sign_secretkey($keypair);

// Convert to base58
$publicKeyBase58 = base58_encode($publicKey);

// Save the secret key as JSON array (Solana format)
$secretKeyArray = array_values(unpack('C*', $secretKeyFull));

$walletData = [
    'public_key' => $publicKeyBase58,
    'secret_key' => $secretKeyArray
];

file_put_contents(__DIR__ . '/wallet.json', json_encode($walletData, JSON_PRETTY_PRINT));
chmod(__DIR__ . '/wallet.json', 0600); // Restrict permissions

echo "New Solana Wallet Generated!\n";
echo "============================\n\n";
echo "Public Key (Address): " . $publicKeyBase58 . "\n\n";
echo "Secret key saved to: wallet.json\n";
echo "IMPORTANT: Keep wallet.json secure and private!\n\n";
echo "To fund this wallet, send SOL to: " . $publicKeyBase58 . "\n";
