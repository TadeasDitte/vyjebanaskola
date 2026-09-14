# syntax=docker/dockerfile:1

############################################################################
# base - FrankenPHP (Caddy + PHP 8.4) runtime with extensions + Composer     #
############################################################################
FROM dunglas/frankenphp:1-php8.4-bookworm AS base

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip curl ca-certificates postgresql-client \
    && install-php-extensions \
        pdo_pgsql pgsql bcmath pcntl intl zip gd exif \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini

# Plain HTTP on :80 - TLS is a fronting proxy's job. Overridable at runtime
# (e.g. SERVER_NAME=example.com for Caddy's automatic HTTPS).
ENV SERVER_NAME=:80

WORKDIR /var/www/html
# www-data's home is /var/www; npm/composer write caches there. Caddy keeps
# its state in /data and /config (XDG_* in the base image); the setcap lets
# the non-root process bind :80.
RUN chown -R www-data:www-data /var/www /data /config \
    && setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp
ENV HOME=/var/www

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]

############################################################################
# build - base + Node 22 toolchain, all deps installed. Used by CI to run    #
#         the PHP checks and as the stage the frontend build starts from.    #
#         (Local development uses Laravel Sail, not this image.)             #
############################################################################
FROM base AS build

RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

USER www-data

COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-scripts --no-interaction --prefer-dist

COPY --chown=www-data:www-data package.json package-lock.json ./
RUN npm ci

COPY --chown=www-data:www-data . .
RUN composer dump-autoload --optimize

############################################################################
# frontend - build production assets (PHP is present so the Wayfinder /      #
#            Inertia Vite plugins that shell out to `php artisan` work)      #
############################################################################
FROM build AS frontend
RUN npm run build

############################################################################
# production - slim FrankenPHP image, no Node, prebuilt assets baked in      #
############################################################################
FROM base AS production
ENV APP_ENV=production

USER www-data

COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

COPY --chown=www-data:www-data . .
COPY --from=frontend --chown=www-data:www-data /var/www/html/public/build ./public/build
COPY --from=frontend --chown=www-data:www-data /var/www/html/resources/js/actions ./resources/js/actions
COPY --from=frontend --chown=www-data:www-data /var/www/html/resources/js/routes ./resources/js/routes
COPY --from=frontend --chown=www-data:www-data /var/www/html/resources/js/wayfinder ./resources/js/wayfinder

RUN composer dump-autoload --optimize --classmap-authoritative \
    && php artisan config:clear
