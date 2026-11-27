#!/bin/bash
# BY RASLAN ERP — Oracle Always Free (Ubuntu 22.04/24.04, ARM Ampere) one-shot setup.
# Run as a user with sudo on a FRESH server. It installs Docker, clones the repo,
# and starts the production stack on port 80.
# Usage:
#   curl -fsSL https://raw.githubusercontent.com/Emam-Medhat/By-Raslan/main/deploy/oracle-setup.sh | bash
# Or: git clone the repo first, then: bash deploy/oracle-setup.sh
set -euo pipefail

APP_DIR="${APP_DIR:-$HOME/By-Raslan}"
APP_URL="${APP_URL:-}"   # e.g. https://erp.yourdomain.com - leave empty to use the server IP

echo "==> Installing Docker..."
if ! command -v docker >/dev/null 2>&1; then
  sudo apt-get update -y
  sudo apt-get install -y ca-certificates curl git
  sudo install -m 0755 -d /etc/apt/keyrings
  curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
  sudo chmod a+r /etc/apt/keyrings/docker.gpg
  echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
  sudo apt-get update -y
  sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
  sudo usermod -aG docker "$USER" || true
fi

echo "==> Cloning By-Raslan..."
if [ ! -d "$APP_DIR/.git" ]; then
  git clone https://github.com/Emam-Medhat/By-Raslan.git "$APP_DIR"
else
  git -C "$APP_DIR" pull --ff-only || true
fi
cd "$APP_DIR"

echo "==> Writing .env for production..."
if [ ! -f .env ]; then
  cp .env.example .env
fi
# Strong random passwords (only used if not already set in .env)
DB_PASS="$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2- || true)"
if [ -z "$DB_PASS" ] || [ "$DB_PASS" = "secret" ]; then
  DB_PASS="$(openssl rand -hex 16)"
fi
ROOT_PASS="$(openssl rand -hex 16)"
{
  echo "APP_NAME=BY RASLAN ERP"
  echo "APP_URL=${APP_URL:-http://$(curl -fsSL https://ifconfig.me || echo localhost)}"
  echo "DB_DATABASE=byraslan"
  echo "DB_USERNAME=byraslan"
  echo "DB_PASSWORD=$DB_PASS"
  echo "DB_ROOT_PASSWORD=$ROOT_PASS"
} > .env.prod
# Keep secrets out of the repo checkout: load them as shell vars for compose
set -a; . ./.env.prod; set +a

echo "==> Starting production stack..."
sudo docker compose -f docker-compose.prod.yml up -d --build

echo "==> Waiting for the app to answer..."
for i in $(seq 1 30); do
  if curl -fsS -o /dev/null "http://127.0.0.1/login" 2>/dev/null; then
    echo "OK: app is up at http://127.0.0.1/login"
    break
  fi
  sleep 10
done

echo
echo "DONE. Open the server public IP in a browser (port 80)."
echo "Then set APP_URL to your final domain in .env.prod and re-run:"
echo "  docker compose -f docker-compose.prod.yml up -d"
echo "For a permanent Cloudflare Tunnel on this server, see DEPLOY.md."
