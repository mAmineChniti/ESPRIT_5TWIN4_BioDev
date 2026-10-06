# ---------- PHP dependencies ----------
FROM php:8.3-cli-alpine AS vendor
RUN docker-php-ext-install pdo pdo_mysql pcntl bcmath
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction --prefer-dist

# ---------- Frontend build (needs vendor/ for the April UI stylesheet) ----------
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci
COPY --from=vendor /app/vendor ./vendor
COPY resources/ resources/
COPY vite.config.js ./
COPY public/ public/
RUN npm run build

# ---------- Runtime ----------
FROM php:8.3-cli-alpine
RUN docker-php-ext-install pdo pdo_mysql pcntl bcmath
WORKDIR /app

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=frontend /app/public/build ./public/build

# Writable state is owned by the unprivileged user the app runs as.
RUN mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache \
             storage/logs storage/app bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

USER www-data

EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD wget -qO- http://127.0.0.1:8000/up > /dev/null || exit 1

CMD ["sh", "/app/docker/entrypoint.sh"]