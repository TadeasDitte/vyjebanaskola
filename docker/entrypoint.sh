#!/bin/sh
set -e

cd /var/www/html

# --- Dev/CI scaffolding ------------------------------------------------------
# Skipped in production, where APP_KEY arrives as a real env var (env_file in
# docker-compose.prod.yml) and the baked .env-less image must stay untouched.
if [ -z "${APP_KEY:-}" ]; then
    if [ ! -f vendor/autoload.php ]; then
        echo "[entrypoint] vendor/ missing - running composer install..."
        composer install --no-interaction --prefer-dist
    fi

    if [ ! -f .env ] && [ -f .env.example ]; then
        echo "[entrypoint] creating .env from .env.example"
        cp .env.example .env
    fi

    if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
        echo "[entrypoint] generating APP_KEY"
        php artisan key:generate --force
    fi
fi

# --- Export public/ into the volume nginx serves (prod) ------------------------
# Runs before the DB wait so nginx has assets as soon as possible.
if [ -n "${PUBLIC_EXPORT_DIR:-}" ]; then
    php artisan storage:link --force 2>/dev/null || true
    echo "[entrypoint] syncing public/ to ${PUBLIC_EXPORT_DIR}"
    find "${PUBLIC_EXPORT_DIR}" -mindepth 1 -delete
    cp -a public/. "${PUBLIC_EXPORT_DIR}/"
fi

# --- Wait for the database (connection details come from env / .env) -----------
echo "[entrypoint] waiting for the database..."
tries=0
until php artisan db:show >/dev/null 2>&1; do
    tries=$((tries + 1))
    if [ "$tries" -ge 60 ]; then
        echo "[entrypoint] database not reachable after 60s, continuing anyway"
        break
    fi
    sleep 1
done

# --- Migrations run only from the main app container ------------------------------
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "[entrypoint] running migrations"
    php artisan migrate --force
    php artisan storage:link --force 2>/dev/null || true
fi

# --- Cache config/routes/views in production -----------------------------------
if [ "${APP_ENV:-}" = "production" ]; then
    echo "[entrypoint] php artisan optimize"
    php artisan optimize || true
fi

exec "$@"
