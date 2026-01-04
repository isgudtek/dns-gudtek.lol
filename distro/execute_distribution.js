#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

// Check if @solana/web3.js is available
let solanaWeb3;
try {
    solanaWeb3 = require('@solana/web3.js');
} catch (e) {
    console.error('Error: @solana/web3.js not installed. Install with: npm install @solana/web3.js');
    process.exit(1);
}

const { Connection, Keypair, Transaction, SystemProgram, PublicKey, sendAndConfirmTransaction, LAMPORTS_PER_SOL } = solanaWeb3;

// Load wallet
function loadWallet() {
    const walletPath = path.join(__dirname, 'wallet.json');
    const walletData = JSON.parse(fs.readFileSync(walletPath, 'utf8'));
    return Keypair.fromSecretKey(new Uint8Array(walletData.secret_key));
}

// Main distribution function
async function executeDistribution(distributions) {
    const RPC_URL = 'https://mainnet.helius-rpc.com/?api-key=13eb1a93-5010-4ae1-9352-d4171b64a57b';
    const connection = new Connection(RPC_URL, 'confirmed');

    const wallet = loadWallet();
    console.log('Loaded wallet:', wallet.publicKey.toString());

    // Get current balance
    const balance = await connection.getBalance(wallet.publicKey);
    console.log('Current balance:', balance / LAMPORTS_PER_SOL, 'SOL');

    const results = {
        success: true,
        transactions: [],
        errors: []
    };

    // Send transactions in batches
    const BATCH_SIZE = 5; // Send 5 at a time to avoid rate limits

    for (let i = 0; i < distributions.length; i += BATCH_SIZE) {
        const batch = distributions.slice(i, i + BATCH_SIZE);

        console.log(`\nProcessing batch ${Math.floor(i / BATCH_SIZE) + 1}/${Math.ceil(distributions.length / BATCH_SIZE)}`);

        for (const dist of batch) {
            try {
                const recipientPubkey = new PublicKey(dist.wallet);
                const lamports = Math.floor(dist.amount_lamports);

                if (lamports < 1) {
                    console.log(`Skipping ${dist.wallet} - amount too small`);
                    continue;
                }

                // Create transaction
                const transaction = new Transaction().add(
                    SystemProgram.transfer({
                        fromPubkey: wallet.publicKey,
                        toPubkey: recipientPubkey,
                        lamports: lamports
                    })
                );

                // Send transaction
                console.log(`Sending ${dist.amount_sol.toFixed(6)} SOL to ${dist.wallet.slice(0, 8)}...`);

                const signature = await sendAndConfirmTransaction(
                    connection,
                    transaction,
                    [wallet],
                    {
                        commitment: 'confirmed',
                        preflightCommitment: 'confirmed'
                    }
                );

                console.log(`✓ Success! TX: ${signature}`);

                results.transactions.push({
                    wallet: dist.wallet,
                    amount: dist.amount_sol,
                    signature: signature,
                    status: 'success'
                });

                // Small delay to avoid rate limiting
                await new Promise(resolve => setTimeout(resolve, 500));

            } catch (error) {
                console.error(`✗ Failed to send to ${dist.wallet}:`, error.message);
                results.errors.push({
                    wallet: dist.wallet,
                    error: error.message
                });
            }
        }

        // Delay between batches
        if (i + BATCH_SIZE < distributions.length) {
            console.log('Waiting 2 seconds before next batch...');
            await new Promise(resolve => setTimeout(resolve, 2000));
        }
    }

    // Final report
    console.log('\n=== DISTRIBUTION COMPLETE ===');
    console.log('Successful:', results.transactions.length);
    console.log('Failed:', results.errors.length);
    console.log('Total sent:', results.transactions.reduce((sum, tx) => sum + tx.amount, 0).toFixed(6), 'SOL');

    return results;
}

// Read distribution data from stdin or file
async function main() {
    try {
        let distributions;

        if (process.argv[2]) {
            // Read from file
            const data = fs.readFileSync(process.argv[2], 'utf8');
            distributions = JSON.parse(data);
        } else {
            // Read from stdin
            const stdin = fs.readFileSync(0, 'utf8');
            distributions = JSON.parse(stdin);
        }

        const results = await executeDistribution(distributions);

        // Output results as JSON
        console.log('\n=== RESULTS JSON ===');
        console.log(JSON.stringify(results, null, 2));

        process.exit(results.errors.length > 0 ? 1 : 0);
    } catch (error) {
        console.error('Fatal error:', error);
        process.exit(1);
    }
}

main();
