#!/bin/sh
set -e

echo "==> 枕邊遊戲 PillowPlay — Starting up..."

# ── Sync migrations from image into volume ──────────
# The db_data volume masks /var/www/html/database/ at runtime, including the
# migrations/ subdirectory. Without this sync, new migration files added in
# image rebuilds never reach the runtime. We do a *replace* sync (not no-clobber)
# so renamed / deleted migrations in source are also reflected in the volume,
# preventing drift where a deleted migration keeps re-running on every boot.
# The volume is intended for the SQLite DB only; the migrations/ directory is
# treated as image-owned, not user-data.
# Guard: skip the sync when /var/www/html is a bind-mounted source checkout
# (.git present) — the replace-sync would delete newer migrations that exist
# only in the working tree and overwrite them with the image's stale copy.
if [ -d /var/migrations-image ] && [ ! -d /var/www/html/.git ]; then
    mkdir -p /var/www/html/database/migrations
    rm -f /var/www/html/database/migrations/*.php
    cp /var/migrations-image/*.php /var/www/html/database/migrations/ 2>/dev/null || true
    chown -R www-data:www-data /var/www/html/database/migrations
fi

# ── Generate APP_KEY if missing ──────────────────────
if [ -z "$APP_KEY" ]; then
    echo "==> Generating APP_KEY..."
    php artisan key:generate --no-interaction --force
fi

# ── SQLite: create database file if this is a fresh volume ──
DB_FILE="/var/www/html/database/database.sqlite"
IS_FRESH_DB=false

if [ ! -f "$DB_FILE" ]; then
    echo "==> Creating fresh SQLite database..."
    touch "$DB_FILE"
    chown www-data:www-data "$DB_FILE"
    IS_FRESH_DB=true
fi

# ── Run migrations ───────────────────────────────────
echo "==> Running migrations..."
php artisan migrate --force --no-interaction

# ── Seed default board on first run ─────────────────
if [ "$IS_FRESH_DB" = "true" ]; then
    echo "==> Seeding default board..."
    php artisan db:seed --force --no-interaction || true
fi

# ── Storage symlink ──────────────────────────────────
php artisan storage:link --no-interaction 2>/dev/null || true

# ── Cache configuration for production ───────────────
if [ "$APP_ENV" = "production" ]; then
    echo "==> Caching config / routes / views..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# ── Fix permissions after volume mount ───────────────
chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    /var/www/html/database

echo "==> Starting Nginx + PHP-FPM..."
exec supervisord -c /etc/supervisord.conf
