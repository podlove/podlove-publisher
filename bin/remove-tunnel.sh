#!/usr/bin/env bash
set -euo pipefail

# Reset WordPress URLs back to local defaults after using a tunnel.
# Run this from within a Local site shell (where wp-config.php is present)
# or from the plugin root when using wp-env.
#
# Usage:
#   bin/remove-tunnel.sh
#
# Override defaults with:
#   LOCAL_HOST=publisher.local LOCAL_PORT=80 LOCAL_SCHEME=https bin/remove-tunnel.sh

if ! command -v wp >/dev/null 2>&1; then
  echo "Error: wp (WP-CLI) is not available in PATH." >&2
  exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
LOCAL_WP_ENV_BIN="${REPO_ROOT}/node_modules/.bin/wp-env"

# Local defaults (Local app). wp-env defaults to localhost:8888.
LOCAL_HOST="${LOCAL_HOST:-localhost}"
LOCAL_SCHEME="${LOCAL_SCHEME:-http}"
LOCAL_PORT="${LOCAL_PORT:-8888}"
LOCAL_URL="${LOCAL_SCHEME}://${LOCAL_HOST}:${LOCAL_PORT}/"
WP_URL="${WP_URL:-$LOCAL_URL}"

set_wp_cli() {
  if [ -f "wp-config.php" ]; then
    WP="wp"
    return
  fi

  if command -v wp-env >/dev/null 2>&1; then
    WP="wp-env run cli wp"
    return
  fi

  if [ -x "${LOCAL_WP_ENV_BIN}" ]; then
    WP="${LOCAL_WP_ENV_BIN} run cli wp"
    return
  fi

  if command -v npx >/dev/null 2>&1; then
    if npx --no-install wp-env --version >/dev/null 2>&1; then
      WP="npx --no-install wp-env run cli wp"
      return
    fi
  fi

  echo "Error: wp-config.php not found and wp-env is not available." >&2
  echo "Run this from a WordPress root or from the plugin root with wp-env installed." >&2
  echo "Tip: run npm install (or npm run wp-env:start) to install wp-env locally." >&2
  exit 1
}

set_wp_cli

cat <<INFO
Resetting WordPress URLs to local defaults:
  Local URL: ${LOCAL_URL}
  URL:       ${WP_URL}
INFO

${WP} option update home "${WP_URL}" >/dev/null 2>&1 || true
${WP} option update siteurl "${WP_URL}" >/dev/null 2>&1 || true
${WP} rewrite flush --hard >/dev/null 2>&1 || true

# Local/wp-env may inject redirects via wp-config constants. Clear then set.
${WP} config delete WP_HOME --type=constant >/dev/null 2>&1 || true
${WP} config delete WP_SITEURL --type=constant >/dev/null 2>&1 || true
${WP} config set WP_HOME "${WP_URL}" --type=constant >/dev/null 2>&1 || true
${WP} config set WP_SITEURL "${WP_URL}" --type=constant >/dev/null 2>&1 || true

echo "Done. WordPress URL reset to local defaults."
