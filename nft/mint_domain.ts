import fetch from 'node-fetch';
import {
  Connection,
  PublicKey,
  Transaction,
  Keypair,
} from '@solana/web3.js';
import {
  createMintV1Instruction,
  PROGRAM_ID as BUBBLEGUM_PROGRAM_ID,
  MetadataArgs,
} from '@metaplex-foundation/mpl-bubblegum';
import {
  SPL_ACCOUNT_COMPRESSION_PROGRAM_ID,
  SPL_NOOP_PROGRAM_ID,
} from '@solana/spl-account-compression';
import fs from 'fs';
import os from 'os';
import path from 'path';

// Command line args: domain slug, buyer wallet, fee payer keypair
const [, , slug, buyerWallet, feePayerKeypath] = process.argv;
if (!slug || !buyerWallet || !feePayerKeypath) {
  console.error("❌ Usage: npx ts-node mint_domain.ts <slug> <buyerWallet> <feePayerKeypair>");
  process.exit(1);
}

function expand(p: string): string {
  return p.replace(/^~(?=$|\/|\\)/, os.homedir());
}

function loadWallet(file: string): Keypair {
  const keyData = JSON.parse(fs.readFileSync(expand(file), "utf8"));
  return Keypair.fromSecretKey(Uint8Array.from(keyData));
}

async function main() {
  // TODO: Update with your actual Helius RPC key
  const RPC = "https://mainnet.helius-rpc.com/?api-key=YOUR_HELIUS_KEY";

  // TODO: Update these with your actual addresses after tree/collection creation
  const MERKLE_TREE = new PublicKey("YOUR_MERKLE_TREE_ADDRESS");
  const COLLECTION_MINT = new PublicKey("YOUR_COLLECTION_MINT_ADDRESS");
  const COLLECTION_METADATA = new PublicKey("YOUR_COLLECTION_METADATA_ADDRESS");
  const COLLECTION_AUTHORITY = new PublicKey("YOUR_COLLECTION_AUTHORITY_ADDRESS");

  const connection = new Connection(RPC, "confirmed");
  const payer = loadWallet(feePayerKeypath);
  const recipient = new PublicKey(buyerWallet);

  // Calculate tree authority PDA
  const [treeAuthority] = await PublicKey.findProgramAddress(
    [MERKLE_TREE.toBuffer()],
    BUBBLEGUM_PROGRAM_ID
  );

  // Calculate Bubblegum signer PDA
  const [bubblegumSigner] = await PublicKey.findProgramAddress(
    [Buffer.from("collection_cpi", "utf8")],
    BUBBLEGUM_PROGRAM_ID
  );

  // Metadata for the domain NFT
  const metadata: MetadataArgs = {
    name: `${slug}.gudtek.lol`,
    symbol: "GUDTEK",
    uri: `https://is.gudtek.lol/nft/metadata/${slug}.json`,
    sellerFeeBasisPoints: 500, // 5% royalty
    primarySaleHappened: true,
    isMutable: true,
    editionNonce: null,
    tokenStandard: null,
    collection: {
      verified: false,
      key: COLLECTION_MINT,
    },
    uses: null,
    tokenProgramVersion: 'Token',
    creators: [
      {
        address: payer.publicKey,
        verified: true,
        share: 100,
      },
    ],
  };

  // Create mint instruction
  const mintIx = createMintV1Instruction(
    {
      merkleTree: MERKLE_TREE,
      treeAuthority,
      treeDelegate: payer.publicKey,
      payer: payer.publicKey,
      leafOwner: recipient,
      leafDelegate: recipient,
      compressionProgram: SPL_ACCOUNT_COMPRESSION_PROGRAM_ID,
      logWrapper: SPL_NOOP_PROGRAM_ID,
    },
    {
      message: metadata,
    }
  );

  // Build and send transaction
  const tx = new Transaction().add(mintIx);
  tx.feePayer = payer.publicKey;
  tx.recentBlockhash = (await connection.getLatestBlockhash()).blockhash;

  console.log(`🎨 Minting cNFT for ${slug}.gudtek.lol to ${buyerWallet}...`);

  tx.sign(payer);
  const rawTx = tx.serialize();
  const sig = await connection.sendRawTransaction(rawTx, { skipPreflight: false });

  console.log(`⏳ Transaction sent: ${sig}`);

  await connection.confirmTransaction(sig, "confirmed");

  console.log(`✅ Domain NFT minted successfully!`);
  console.log(`   Signature: ${sig}`);
  console.log(`   Explorer: https://solscan.io/tx/${sig}`);

  // Return JSON for PHP to parse
  console.log(JSON.stringify({
    success: true,
    signature: sig,
    slug: slug,
    owner: buyerWallet
  }));
}

main().catch((err) => {
  console.error("❌ Error:", err);
  console.log(JSON.stringify({
    success: false,
    error: err.message
  }));
  process.exit(1);
});
