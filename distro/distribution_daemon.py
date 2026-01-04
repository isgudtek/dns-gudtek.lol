#!/usr/bin/env python3
"""
Lightweight distribution daemon - runs continuously in background
Monitors countdown timer and triggers distributions automatically
"""

import json
import time
import requests
import sys
import os
from pathlib import Path
from datetime import datetime

# Configuration
CONFIG_FILE = Path(__file__).parent / 'config.json'
ACTIVITY_LOG_FILE = Path(__file__).parent / 'daemon_activity.json'
STATUS_FILE = Path(__file__).parent / 'daemon_status.json'
API_URL = 'https://is.gudtek.lol/distro/api.php'
ADMIN_WALLET = 'DmXxdBDNxe7Rrq1SReV6FQcG1cswrL2MHxYQPf4J9XdS'
CHECK_INTERVAL = 1  # Check every 1 second
MAX_LOG_ENTRIES = 100

def load_config():
    """Load configuration from JSON file"""
    try:
        with open(CONFIG_FILE, 'r') as f:
            return json.load(f)
    except Exception as e:
        print(f"Error loading config: {e}", file=sys.stderr)
        return None

def write_log(message, log_type='info'):
    """Write log entry to shared activity log file"""
    try:
        # Load existing logs
        if ACTIVITY_LOG_FILE.exists():
            with open(ACTIVITY_LOG_FILE, 'r') as f:
                logs = json.load(f)
        else:
            logs = []

        # Add new log entry
        logs.insert(0, {
            'timestamp': int(time.time()),
            'message': message,
            'type': log_type,
            'source': 'daemon'
        })

        # Keep only last MAX_LOG_ENTRIES
        logs = logs[:MAX_LOG_ENTRIES]

        # Save back to file
        with open(ACTIVITY_LOG_FILE, 'w') as f:
            json.dump(logs, f)

        # Also print to stdout
        print(message)
    except Exception as e:
        print(f"Error writing log: {e}", file=sys.stderr)

def update_heartbeat():
    """Update daemon status heartbeat"""
    try:
        status = {
            'running': True,
            'last_heartbeat': int(time.time()),
            'pid': os.getpid()
        }
        with open(STATUS_FILE, 'w') as f:
            json.dump(status, f)
    except Exception as e:
        print(f"Error updating heartbeat: {e}", file=sys.stderr)

def collect_royalties():
    """Collect PumpPortal creator fees before distribution"""
    try:
        write_log("💰 Collecting PumpPortal royalties...", 'info')
        response = requests.post(API_URL, json={
            'action': 'collectRoyalties',
            'wallet': ADMIN_WALLET
        }, timeout=60)

        data = response.json()
        if data.get('success'):
            sig = data.get('signature', 'N/A')
            write_log(f"✓ Royalties collected! TX: {sig[:16]}...", 'success')
            return True
        else:
            # Not an error if no royalties available
            error = data.get('error', 'No royalties to collect')
            write_log(f"ℹ Royalty collection: {error}", 'info')
            return True  # Continue anyway
    except Exception as e:
        write_log(f"⚠ Royalty collection warning: {e}", 'warning')
        return True  # Continue with distribution even if royalty collection fails

def execute_distribution():
    """Execute distribution via API"""
    try:
        response = requests.post(API_URL, json={
            'action': 'executeDistribution',
            'wallet': ADMIN_WALLET
        }, timeout=60)

        data = response.json()
        if data.get('success'):
            write_log(f"✓ Distribution completed: {data.get('total_amount', 0):.4f} SOL to {data.get('recipients', 0)} holders", 'success')
            return True
        else:
            write_log(f"✗ Distribution failed: {data.get('error', 'Unknown error')}", 'error')
            return False
    except Exception as e:
        write_log(f"✗ Distribution error: {e}", 'error')
        return False

def restart_timer():
    """Restart timer for next cycle"""
    try:
        response = requests.post(API_URL, json={
            'action': 'startTimer',
            'wallet': ADMIN_WALLET
        }, timeout=10)

        data = response.json()
        if data.get('success'):
            write_log("↻ Timer restarted for next cycle", 'info')
            return True
        else:
            write_log(f"✗ Failed to restart timer: {data.get('error', 'Unknown error')}", 'error')
            return False
    except Exception as e:
        write_log(f"✗ Timer restart error: {e}", 'error')
        return False

def main():
    """Main daemon loop"""
    write_log("=== Distribution Daemon Started ===", 'success')
    print(f"Checking every {CHECK_INTERVAL} second(s)")
    print(f"Config: {CONFIG_FILE}")
    print(f"API: {API_URL}\n")

    last_execution = 0

    while True:
        try:
            # Update heartbeat every iteration
            update_heartbeat()

            config = load_config()

            if not config:
                time.sleep(CHECK_INTERVAL)
                continue

            # Check if system is enabled and timer is running
            if not config.get('timer_running') or not config.get('enabled'):
                time.sleep(CHECK_INTERVAL)
                continue

            # Calculate time remaining
            now = int(time.time())
            elapsed = now - config.get('countdown_start_time', now)
            interval = config.get('interval_seconds', 86400)
            time_remaining = interval - elapsed

            # Execute distribution when time is up
            if time_remaining <= 0 and now - last_execution > 5:  # Prevent double-execution
                write_log("⏰ Countdown reached 0 - starting cycle...", 'warning')

                # Step 1: Collect royalties from PumpPortal
                collect_royalties()
                time.sleep(1)  # Brief pause between operations

                # Step 2: Execute distribution
                if execute_distribution():
                    last_execution = now
                    time.sleep(2)  # Brief pause before restarting
                    restart_timer()
                else:
                    # Even on failure, restart timer
                    time.sleep(2)
                    restart_timer()
                    last_execution = now

            time.sleep(CHECK_INTERVAL)

        except KeyboardInterrupt:
            write_log("=== Daemon stopped by user ===", 'warning')
            break
        except Exception as e:
            write_log(f"Error in main loop: {e}", 'error')
            time.sleep(CHECK_INTERVAL)

if __name__ == '__main__':
    main()
