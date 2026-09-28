#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

git config --global --add safe.directory "$PWD"

composer install --no-interaction

if [ ! -f .env ]; then
    cp .env.example .env
fi
if ! grep -q '^APP_KEY=.\+' .env; then
    php artisan key:generate --no-interaction
fi

php lnms migrate --force --no-interaction

# local snmpsim venv, used by lnms dev:check and lnms dev:simulate
php lnms dev:simulate --setup-venv
