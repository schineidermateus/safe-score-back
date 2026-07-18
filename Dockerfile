FROM php:8.4-fpm-bookworm AS base

RUN apt-get update \
    && apt-get install -y --no-install-recommends git libfcgi-bin libicu-dev libzip-dev unzip \
    && docker-php-ext-install -j"$(nproc)" bcmath intl opcache pdo_mysql zip \
    && php -r 'if (!extension_loaded("bcmath") || !function_exists("bccomp")) { fwrite(STDERR, "BCMath extension installation failed.\n"); exit(1); }' \
    && rm -rf /var/lib/apt/lists/* \
    && sed -i 's|;ping.path = /ping|ping.path = /ping|' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's|;clear_env = no|clear_env = no|' /usr/local/etc/php-fpm.d/www.conf

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/php.ini /usr/local/etc/php/conf.d/99-safescore.ini

WORKDIR /app

EXPOSE 9000

FROM base AS development

ENV APP_ENV=dev

COPY docker/entrypoint.sh /usr/local/bin/safescore-entrypoint

RUN chmod +x /usr/local/bin/safescore-entrypoint \
    && mkdir -p /app/vendor \
    && chown -R www-data:www-data /app

USER www-data

ENTRYPOINT ["safescore-entrypoint"]
CMD ["php-fpm", "-F"]

FROM base AS production

ENV APP_ENV=prod

COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts

COPY . .
RUN rm -rf src/Shared/Infrastructure/Fixtures \
    && composer dump-autoload --optimize --no-dev \
    && php bin/console cache:warmup \
    && chown -R www-data:www-data var

USER www-data

CMD ["php-fpm", "-F"]
