# syntax=docker/dockerfile:1

############################################################################
# base - PHP 8.4 FPM runtime with extensions + Composer                      #
############################################################################
FROM php:8.4-fpm-bookworm AS base

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip curl ca-certificates libpq5 postgresql-client \
    && install-php-extensions \
        pdo_pgsql pgsql bcmath pcntl intl zip opcache gd exif \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini

# Align the container user with the host user so bind-mounted files stay
# writable from both sides (override with build args if your host uid != 1000).
ARG UID=1000
ARG GID=1000
RUN groupmod -o -g "${GID}" www-data \
    && usermod  -o -u "${UID}" -g "${GID}" www-data

WORKDIR /var/www/html
# www-data's home is /var/www; npm/composer write caches there.
RUN chown -R www-data:www-data /var/www
ENV HOME=/var/www

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

############################################################################
# dev - base + Node 22 toolchain, all deps installed (source is bind-mounted #
#       by docker-compose; named volumes are seeded from this image)         #
############################################################################
FROM base AS dev

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
FROM dev AS frontend
RUN npm run build

############################################################################
# production - slim PHP-FPM image, no Node, prebuilt assets baked in         #
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
