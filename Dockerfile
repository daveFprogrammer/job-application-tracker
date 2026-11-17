# syntax=docker/dockerfile:1

# Production image (Render). Local development uses Laravel Sail (compose.yaml).
#
#   base   -> FrankenPHP + the PHP extensions the app needs
#   build  -> composer + npm: vendor/ and the Vite bundle
#   final  -> base + the built app, no dev dependencies, no Node

FROM dunglas/frankenphp:1-php8.4 AS base

RUN install-php-extensions pdo_pgsql intl zip opcache pcntl bcmath

COPY docker/php.ini $PHP_INI_DIR/conf.d/zz-app.ini

WORKDIR /app


FROM base AS build

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY . .

# The Wayfinder Vite plugin boots artisan to generate the typed routes.
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
    && npm run build \
    && rm -rf node_modules


FROM base

COPY --from=build --chown=www-data:www-data /app /app
COPY docker/Caddyfile /etc/frankenphp/Caddyfile
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/app-entrypoint

# Run as an unprivileged user: Caddy needs its data/config dirs to be writable.
RUN chown -R www-data:www-data /data/caddy /config/caddy

ENV APP_ENV=production \
    LOG_CHANNEL=stderr \
    PORT=8080

USER www-data

EXPOSE 8080

ENTRYPOINT ["app-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
