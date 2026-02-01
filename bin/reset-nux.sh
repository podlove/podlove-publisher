#!/usr/bin/env bash
set -euo pipefail

# Full WordPress reset + fresh install for new-user experience testing.
# Run this from within a Local site shell (where wp-config.php is present)
# or from the plugin root when using wp-env.
#
# Usage:
#   bin/reset-nux.sh               # reset using LOCAL_URL
#   bin/reset-nux.sh --tunnel      # reset and set site URLs to ngrok public URL
#
# wp-env defaults:
# - Run from plugin root; wp-env exposes WP at http://localhost:8888 by default.
# - Override host/port (e.g. pretty URLs) using wp-env + Caddy:
#   1) Add a .wp-env.override.json like:
#      {
#        "port": 80,
#        "host": "publisher.local"
#      }
#   2) Add to /etc/hosts:
#      127.0.0.1 publisher.local
#   3) Run Caddy to serve HTTPS locally:
#      caddy reverse-proxy --from https://publisher.local --to http://localhost:80
#   4) Then run:
#      LOCAL_HOST=publisher.local LOCAL_PORT=80 LOCAL_SCHEME=https bin/reset-nux.sh

if ! command -v wp >/dev/null 2>&1; then
  echo "Error: wp (WP-CLI) is not available in PATH." >&2
  exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
LOCAL_WP_ENV_BIN="${REPO_ROOT}/node_modules/.bin/wp-env"

TUNNEL=false
while [ $# -gt 0 ]; do
  case "$1" in
    --tunnel)
      TUNNEL=true
      ;;
    *)
      echo "Error: unknown argument: $1" >&2
      echo "Usage: $0 [--tunnel]" >&2
      exit 1
      ;;
  esac
  shift
done

# Local defaults (Local app). wp-env defaults to localhost:8888.
LOCAL_HOST="${LOCAL_HOST:-localhost}"
LOCAL_SCHEME="${LOCAL_SCHEME:-http}"
LOCAL_PORT="${LOCAL_PORT:-8888}"
LOCAL_URL="${LOCAL_SCHEME}://${LOCAL_HOST}:${LOCAL_PORT}/"
WP_URL="${WP_URL:-$LOCAL_URL}"
WP_TITLE="${WP_TITLE:-NUX Test}"
WP_ADMIN_USER="${WP_ADMIN_USER:-admin}"
WP_ADMIN_PASS="${WP_ADMIN_PASS:-admin}"
WP_ADMIN_EMAIL="${WP_ADMIN_EMAIL:-admin@example.com}"
PLUGIN_SLUG="${PLUGIN_SLUG:-podlove-podcasting-plugin-for-wordpress}"
NGROK_PID=""
NGROK_LOG=""

cleanup() {
  if [ "${TUNNEL}" = true ]; then
    return
  fi
  if [ -n "${NGROK_PID}" ] && kill -0 "${NGROK_PID}" >/dev/null 2>&1; then
    kill "${NGROK_PID}" >/dev/null 2>&1 || true
  fi
}

trap cleanup EXIT

print_ngrok_session_limit_hint() {
  if ! rg -q "ERR_NGROK_108|simultaneous ngrok agent sessions|limited to 1 simultaneous" "${NGROK_LOG}" 2>/dev/null; then
    return
  fi

  echo >&2
  echo "ngrok reports another agent session is already running." >&2
  echo "Find running ngrok processes:" >&2
  echo "  pgrep -fl ngrok" >&2
  echo "Stop them:" >&2
  echo "  pkill -f ngrok" >&2
}

print_ngrok_authtoken_hint() {
  if ! rg -q "ERR_NGROK_4018|authtoken|authentication failed" "${NGROK_LOG}" 2>/dev/null; then
    return
  fi

  echo >&2
  echo "ngrok needs an authtoken. Fix with:" >&2
  echo "  ngrok config add-authtoken <YOUR_TOKEN>" >&2
}

start_ngrok_tunnel() {
  if ! command -v ngrok >/dev/null 2>&1; then
    echo "Error: ngrok is required for --tunnel but was not found in PATH." >&2
    exit 1
  fi

  if ! curl -fsS --max-time 2 "http://127.0.0.1:${LOCAL_PORT}/" >/dev/null 2>&1 \
    && ! curl -fsS --max-time 2 "http://localhost:${LOCAL_PORT}/" >/dev/null 2>&1; then
    echo "Error: local WordPress is not reachable on http://localhost:${LOCAL_PORT}/." >&2
    echo "Check Docker port mappings (wp-env should map ${LOCAL_PORT}->80):" >&2
    echo "  docker ps --format '{{.Names}}\\t{{.Ports}}' | rg 'wordpress'" >&2
    echo "If the port differs, set LOCAL_PORT or LOCAL_HOST before running this script." >&2
    exit 1
  fi

  # Tunnel over plain HTTP to Local's router port; Local handles HTTPS itself.
  local tunnel_target="http://${LOCAL_HOST}:${LOCAL_PORT}/"
  echo "Starting ngrok tunnel for: ${tunnel_target}"

  # Start ngrok in the background and resolve the public URL via the local API.
  NGROK_LOG="$(mktemp -t ngrok-reset-nux)"
  local host_header="--host-header=${LOCAL_HOST}:${LOCAL_PORT}"
  case "${WP}" in
    *wp-env*)
      host_header="--host-header=preserve"
      ;;
  esac
  ngrok http "${tunnel_target}" ${host_header} --log=stdout >"${NGROK_LOG}" 2>&1 &
  NGROK_PID=$!
  echo "ngrok PID: ${NGROK_PID}"
  echo "ngrok log: ${NGROK_LOG}"

  sleep 1

  if ! kill -0 "${NGROK_PID}" >/dev/null 2>&1; then
    echo "Error: ngrok exited immediately." >&2
    echo "ngrok output:" >&2
    sed -n '1,80p' "${NGROK_LOG}" >&2 || true
    print_ngrok_session_limit_hint
    print_ngrok_authtoken_hint
    exit 1
  fi

  local tunnel_url=""
  local attempts=0
  local max_attempts=20

  while [ ${attempts} -lt ${max_attempts} ]; do
    attempts=$((attempts + 1))

    if ! kill -0 "${NGROK_PID}" >/dev/null 2>&1; then
      echo "Error: ngrok stopped while waiting for the tunnel URL." >&2
      echo "ngrok output:" >&2
      sed -n '1,120p' "${NGROK_LOG}" >&2 || true
      exit 1
    fi

    tunnel_url="$(
      curl -fsS http://127.0.0.1:4040/api/tunnels 2>/dev/null \
        | php -n -r '$d=json_decode(stream_get_contents(STDIN), true); if (!is_array($d)) { exit(1); } foreach (($d["tunnels"] ?? []) as $t) { $u=$t["public_url"] ?? ""; if (strpos($u, "https://") === 0) { echo $u; exit(0); } } exit(1);' \
        || true
    )"

    if [ -n "${tunnel_url}" ]; then
      echo "ngrok public URL: ${tunnel_url}"
      WP_URL="${tunnel_url}"
      return
    fi

    sleep 1
  done

  echo "Error: ngrok tunnel did not become ready via http://127.0.0.1:4040/api/tunnels." >&2
  echo "ngrok output (first lines):" >&2
  sed -n '1,120p' "${NGROK_LOG}" >&2 || true
  print_ngrok_session_limit_hint
  print_ngrok_authtoken_hint
  echo "Tip: make sure no other ngrok process is running and try again." >&2
  exit 1
}

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

if [ "${TUNNEL}" = true ]; then
  start_ngrok_tunnel
fi

cat <<INFO
Resetting WordPress with:
  Local URL:    ${LOCAL_URL}
  URL:          ${WP_URL}
  Tunnel:       ${TUNNEL}
  Title:        ${WP_TITLE}
  Admin user:   ${WP_ADMIN_USER}
  Admin email:  ${WP_ADMIN_EMAIL}
  Plugin slug:  ${PLUGIN_SLUG}
INFO

${WP} db reset --yes

${WP} core install \
  --url="${WP_URL}" \
  --title="${WP_TITLE}" \
  --admin_user="${WP_ADMIN_USER}" \
  --admin_password="${WP_ADMIN_PASS}" \
  --admin_email="${WP_ADMIN_EMAIL}"

# Persist the URL explicitly for REST callbacks (important for tunnels).
${WP} option update home "${WP_URL}" >/dev/null 2>&1 || true
${WP} option update siteurl "${WP_URL}" >/dev/null 2>&1 || true
${WP} rewrite flush --hard >/dev/null 2>&1 || true

# Local/wp-env may inject redirects via wp-config constants. Clear then set.
${WP} config delete WP_HOME --type=constant >/dev/null 2>&1 || true
${WP} config delete WP_SITEURL --type=constant >/dev/null 2>&1 || true
${WP} config set WP_HOME "${WP_URL}" --type=constant >/dev/null 2>&1 || true
${WP} config set WP_SITEURL "${WP_URL}" --type=constant >/dev/null 2>&1 || true

activate_podlove_plugin() {
  local candidates=(
    "${PLUGIN_SLUG}"
    "podlove-podcast-publisher"
    "podlove-publisher"
    "$(basename "${REPO_ROOT}")"
  )
  local seen=""
  local slug=""

  for slug in "${candidates[@]}"; do
    case " ${seen} " in
      *" ${slug} "*) continue ;;
    esac
    seen="${seen} ${slug}"
    if ${WP} plugin is-installed "${slug}" >/dev/null 2>&1; then
      ${WP} plugin activate "${slug}" || true
      return
    fi
  done

  echo "Warning: Podlove plugin not found. Tried: ${seen# }" >&2
}

# Ensure the plugin repo is active after reinstall.
activate_podlove_plugin

# For convenience, also activate web player
${WP} plugin activate podlove-web-player || true

if [ "${TUNNEL}" = true ]; then
  echo "Done. Fresh install configured for tunnel URL: ${WP_URL}"
  echo "ngrok is still running in the background (PID: ${NGROK_PID})."
  echo "Stop it with: kill ${NGROK_PID}"
  echo "Switch back to local URLs with: bin/remove-tunnel.sh"
else
  echo "Done. Fresh WordPress install and plugin activation attempted."
fi
