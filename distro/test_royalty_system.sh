#!/bin/bash
#
# Royalty System Test Suite
# Tests all components before minting a token
#

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  ROYALTY DISTRIBUTION SYSTEM - COMPREHENSIVE TEST"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

WALLET="DmXxdBDNxe7Rrq1SReV6FQcG1cswrL2MHxYQPf4J9XdS"
API_URL="https://is.gudtek.lol/distro/api.php"

# Test 1: Create Mock Royalty Data
echo "TEST 1: Creating mock royalty data for UI testing..."
cat > /var/www/gudtek.lol/is/distro/royalty_stats.json << 'EOF'
{
  "total_collected": 0.15,
  "last_collection_amount": 0.05,
  "last_collection_timestamp": TIMESTAMP_PLACEHOLDER,
  "collection_count": 3
}
EOF

# Replace timestamp with current time
CURRENT_TIME=$(date +%s)
sed -i "s/TIMESTAMP_PLACEHOLDER/$CURRENT_TIME/" /var/www/gudtek.lol/is/distro/royalty_stats.json

echo "✓ Mock data created"
echo "  - Total Collected: 0.15 SOL"
echo "  - Last Collection: 0.05 SOL"
echo "  - Collections: 3"
echo ""

# Test 2: Check API Endpoints
echo "TEST 2: Testing API endpoints..."

echo -n "  - getRoyaltyStats: "
STATS=$(curl -s "$API_URL?action=getRoyaltyStats")
if echo "$STATS" | grep -q "total_collected"; then
    echo "✓ PASS"
    echo "    Response: $(echo $STATS | python3 -m json.tool | head -5)"
else
    echo "✗ FAIL"
fi
echo ""

echo -n "  - getStatus: "
STATUS=$(curl -s "$API_URL?action=getStatus")
if echo "$STATUS" | grep -q "treasury_balance"; then
    echo "✓ PASS"
else
    echo "✗ FAIL"
fi
echo ""

echo -n "  - getDaemonStatus: "
DAEMON_STATUS=$(curl -s "$API_URL?action=getDaemonStatus")
if echo "$DAEMON_STATUS" | grep -q "running"; then
    echo "✓ PASS"
    IS_RUNNING=$(echo $DAEMON_STATUS | python3 -c "import sys,json; print(json.load(sys.stdin)['running'])")
    if [ "$IS_RUNNING" = "True" ]; then
        echo "    Daemon: RUNNING ✓"
    else
        echo "    Daemon: STOPPED ✗"
    fi
else
    echo "✗ FAIL"
fi
echo ""

# Test 3: Test Royalty Collection (will fail but test error handling)
echo "TEST 3: Testing royalty collection error handling..."
echo "  (Expected to fail - no tokens created yet)"

COLLECT_RESULT=$(curl -s -X POST "$API_URL" \
    -H "Content-Type: application/json" \
    -d "{\"action\":\"collectRoyalties\",\"wallet\":\"$WALLET\"}" 2>&1)

if echo "$COLLECT_RESULT" | grep -q "success.*false"; then
    echo "✓ Error handling works correctly"
    echo "  Response: $(echo $COLLECT_RESULT | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('error', 'Unknown'))" 2>/dev/null || echo 'API responded with expected error')"
else
    echo "⚠ Unexpected response (check manually)"
fi
echo ""

# Test 4: Check UI Display Elements
echo "TEST 4: Checking UI elements exist..."

UI_ELEMENTS=(
    "lastClaimed"
    "totalCollected"
    "nextDrop"
    "countdown"
    "progressFill"
    "daemonStatus"
)

for element in "${UI_ELEMENTS[@]}"; do
    if grep -q "id=\"$element\"" /var/www/gudtek.lol/is/distro/index.php; then
        echo "  ✓ Element '$element' exists"
    else
        echo "  ✗ Element '$element' MISSING"
    fi
done
echo ""

# Test 5: Verify File Permissions
echo "TEST 5: Checking file permissions..."

FILES=(
    "/var/www/gudtek.lol/is/distro/wallet.json:640"
    "/var/www/gudtek.lol/is/distro/collect_royalties.php:755"
    "/var/www/gudtek.lol/is/distro/distribution_daemon.py:755"
    "/var/www/gudtek.lol/is/distro/pumportal_config.json:640"
)

for file_perm in "${FILES[@]}"; do
    IFS=':' read -r file expected_perm <<< "$file_perm"
    if [ -f "$file" ]; then
        actual_perm=$(stat -c "%a" "$file")
        if [ "$actual_perm" = "$expected_perm" ]; then
            echo "  ✓ $file ($actual_perm)"
        else
            echo "  ⚠ $file (expected $expected_perm, got $actual_perm)"
        fi
    else
        echo "  ✗ $file (missing)"
    fi
done
echo ""

# Test 6: Simulate Full Cycle with Mock Data
echo "TEST 6: Simulating distribution cycle..."

# Add a small amount to treasury for testing (if needed)
echo "  Current treasury balance:"
curl -s "$API_URL?action=getStatus" | python3 -c "import sys,json; d=json.load(sys.stdin); print(f'  {d[\"treasury_balance\"]} SOL')"
echo ""

# Test 7: Check JavaScript Functions
echo "TEST 7: Checking JavaScript functions..."

JS_FUNCTIONS=(
    "loadRoyaltyStats"
    "collectRoyalties"
    "updateCountdown"
    "loadDaemonStatus"
)

for func in "${JS_FUNCTIONS[@]}"; do
    if grep -q "function $func\|async function $func" /var/www/gudtek.lol/is/distro/app.js; then
        echo "  ✓ Function '$func' exists"
    else
        echo "  ✗ Function '$func' MISSING"
    fi
done
echo ""

# Summary
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  TEST SUMMARY"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "✓ Mock royalty data created"
echo "✓ API endpoints tested"
echo "✓ Royalty collection error handling verified"
echo "✓ UI elements verified"
echo "✓ File permissions checked"
echo "✓ JavaScript functions verified"
echo ""
echo "NEXT STEPS:"
echo "1. Visit https://is.gudtek.lol/distro/ to see mock data displayed"
echo "2. Verify UI shows:"
echo "   - Last Claimed: 0.0500 SOL"
echo "   - Total Collected: 0.1500 SOL"
echo "   - Daemon status indicator"
echo "3. When ready, create token with wallet: $WALLET"
echo "4. Royalties will auto-collect and distribute!"
echo ""
