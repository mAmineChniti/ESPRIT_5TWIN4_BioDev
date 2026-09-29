#!/bin/sh
# Container entrypoint: materialize runtime environment variables into .env
# (php artisan serve does NOT forward env vars to its web worker),
# then migrate and serve.
set -e

[ -f .env ] || cp .env.example .env

for key in APP_ENV APP_DEBUG APP_URL APP_KEY \
    DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD \
    SESSION_DRIVER CACHE_STORE QUEUE_CONNECTION; do
    val=$(printenv "$key" || true)
    if [ -n "$val" ]; then
        esc=$(printf '%s' "$val" | sed 's/[&|]/\\&/g')
        if grep -q "^${key}=" .env; then
            sed -i "s|^${key}=.*|${key}=${esc}|" .env
        else
            printf '%s=%s\n' "$key" "$esc" >> .env
        fi
    fi
done

if grep -q '^APP_KEY=$' .env; then
    php artisan key:generate --force
fi

php artisan migrate --force

exec php artisan serve --host=0.0.0.0 --port=8000
