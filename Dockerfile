# =====================================================
#  枕邊遊戲 PillowPlay — Production Docker Image
#  PHP 8.2-FPM + Nginx + Supervisor (Alpine, ~120 MB)
# =====================================================
FROM php:8.2-fpm-alpine

# ── System packages ────────────────────────────────
RUN apk add --no-cache \
        nginx \
        supervisor \
        sqlite \
        sqlite-dev \
        curl \
        zip \
        unzip \
        oniguruma-dev \
        libpng-dev

# ── PHP extensions ─────────────────────────────────
RUN docker-php-ext-install \
        pdo \
        pdo_sqlite \
        mbstring \
        bcmath \
        opcache

# OPcache tuning for production
RUN { \
      echo 'opcache.enable=1'; \
      echo 'opcache.memory_consumption=128'; \
      echo 'opcache.interned_strings_buffer=8'; \
      echo 'opcache.max_accelerated_files=10000'; \
      echo 'opcache.revalidate_freq=0'; \
      echo 'opcache.validate_timestamps=0'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# ── Composer ───────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ── App source ─────────────────────────────────────
WORKDIR /var/www/html
COPY . .

# Install PHP dependencies (no dev, optimised autoloader).
# Strict install — composer.lock must be in sync with composer.json before build.
# When you add a new package, regenerate the lock locally (or in a transient
# container) and commit it; do not let production resolve dependencies at build
# time, otherwise a fresh upstream release in the same constraint window can
# break the image without any repo change.
RUN composer install \
        --no-dev \
        --optimize-autoloader \
        --no-interaction \
        --no-scripts

# Ensure required directories exist with correct ownership
RUN mkdir -p storage/framework/{sessions,views,cache} \
             storage/logs \
             bootstrap/cache \
             database \
 && chown -R www-data:www-data storage bootstrap/cache database \
 && chmod -R 775 storage bootstrap/cache database

# Stash migrations in a non-volume path so the entrypoint can re-seed the
# `db_data` named volume on every start. Without this, the SQLite volume
# (which masks /var/www/html/database/) keeps an out-of-date migrations/
# directory across image rebuilds and migrate ignores new files.
RUN mkdir -p /var/migrations-image \
 && cp database/migrations/*.php /var/migrations-image/ 2>/dev/null || true

# ── Nginx ──────────────────────────────────────────
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
# Remove default nginx welcome page
RUN rm -f /etc/nginx/http.d/default.conf.bak 2>/dev/null; \
    ls /etc/nginx/http.d/

# ── Supervisor ─────────────────────────────────────
COPY docker/supervisord.conf /etc/supervisord.conf

# ── Entrypoint ─────────────────────────────────────
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=15s \
    CMD curl -sf http://localhost/up || exit 1

ENTRYPOINT ["/entrypoint.sh"]
