#!/bin/bash
set -e

# Integration test entrypoint script
# This script:
# 1. Waits for EmailEngine to be ready
# 2. Generates an access token using the EmailEngine CLI
# 3. Exports the token for tests
# 4. Runs the tests

REDIS_URL="${REDIS_URL:-redis://redis:6379}"
EMAILENGINE_URL="${EMAILENGINE_URL:-http://emailengine:3000}"
MAX_WAIT=${MAX_WAIT:-120}
TOKEN_DESCRIPTION="Integration Test Token $(date +%s)"

echo "=== EmailEngine PHP SDK Integration Tests ==="
echo "Redis URL: $REDIS_URL"
echo "EmailEngine URL: $EMAILENGINE_URL"
echo ""

# Wait for EmailEngine to be ready (it also needs Redis, so this covers both)
echo "Waiting for EmailEngine to be ready..."
for i in $(seq 1 $MAX_WAIT); do
    if curl -sf "${EMAILENGINE_URL}/v1/stats" > /dev/null 2>&1; then
        echo "EmailEngine is ready!"
        break
    fi
    if [ $i -eq $MAX_WAIT ]; then
        echo "ERROR: EmailEngine did not become ready in time"
        exit 1
    fi
    echo "Waiting... ($i/$MAX_WAIT)"
    sleep 1
done

# Generate access token using EmailEngine CLI
echo ""
echo "Generating access token..."
TOKEN_OUTPUT=$(emailengine tokens issue \
    -d "$TOKEN_DESCRIPTION" \
    -s "*" \
    --dbs.redis="$REDIS_URL" 2>&1)

# Extract token from output (it's the last line containing the token)
export EMAILENGINE_ACCESS_TOKEN=$(echo "$TOKEN_OUTPUT" | grep -E '^[a-f0-9]{64}$' | tail -n1)

if [ -z "$EMAILENGINE_ACCESS_TOKEN" ]; then
    echo "ERROR: Failed to generate access token"
    echo "CLI output:"
    echo "$TOKEN_OUTPUT"
    exit 1
fi

echo "Access token generated: ${EMAILENGINE_ACCESS_TOKEN:0:16}..."

# Export environment variables for tests
export EMAILENGINE_BASE_URL="$EMAILENGINE_URL"
export EMAILENGINE_REDIS_URL="$REDIS_URL"

echo ""
echo "=== Running Integration Tests ==="
echo "Note: Tests must complete within 15 minutes (EmailEngine unlicensed limit)"
echo ""

# Run the provided command (tests)
exec "$@"
