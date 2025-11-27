#!/bin/sh

# Check internet connectivity before running commands that require it
# Returns: 0 if connected, 1 if not
# Does NOT fail the container - calling script should decide what to do

MAX_RETRIES=2
RETRY_DELAY=1
TIMEOUT=3

check_connection() {
    # Try multiple endpoints to avoid false negatives
    ENDPOINTS="8.8.8.8 1.1.1.1"

    for endpoint in ${ENDPOINTS}; do
        if nc -z -w "${TIMEOUT}" "${endpoint}" 53 2>/dev/null; then
            return 0
        fi
    done

    return 1
}

echo "Checking internet connectivity..."

for i in $(seq 1 "${MAX_RETRIES}"); do
    if check_connection; then
        echo "✓ Internet connection available"
        return 0
    fi

    if [ "${i}" -lt "${MAX_RETRIES}" ]; then
        echo "  Retrying (${i}/${MAX_RETRIES})..."
        sleep "${RETRY_DELAY}"
    fi
done

echo "✗ No internet connection detected"
return 1
