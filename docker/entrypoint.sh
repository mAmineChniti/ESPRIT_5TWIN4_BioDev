#!/bin/sh
# Container entrypoint.
#
# Secrets are supplied as real environment variables (see docker-compose.yml) and
# `artisan serve --no-reload` forwards $_ENV to the web worker, so nothing has
# to be written into a .env file on disk.
set -e

KEY_FILE=/app/storage/app/app.key

mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache \
         storage/logs storage/app bootstrap/cache

# A stable APP_KEY: taken from the environment, else reused from the persisted
# storage volume, else generated once and kept there.
if [ -z "${APP_KEY}" ]; then
    if [ -f "$KEY_FILE" ]; then
        APP_KEY=$(cat "$KEY_FILE")
    else
        php artisan key:generate --force --show > "$KEY_FILE"
        APP_KEY=$(cat "$KEY_FILE")
    fi
    export APP_KEY
fi

php artisan migrate --force

exec php artisan serve --no-reload --host=0.0.0.0 --port=8000