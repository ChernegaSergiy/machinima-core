#!/usr/bin/env bash
set -euo pipefail

PROFILES=("core-only" "telegram-webapp" "telegram-bot")

echo "=== Profile Smoke Test ==="
echo

for profile in "${PROFILES[@]}"; do
    echo "--- Testing: $profile ---"

    if ! APP_PROFILE="$profile" php bin/console lint:container 2>&1; then
        echo "FAIL: $profile"
        exit 1
    fi

    echo "PASS: $profile"
    echo
done

echo "=== All profiles passed ==="
