FROM php:8.4-cli AS base

RUN apt-get update \
    && apt-get install -y --no-install-recommends git libicu-dev libzip-dev unzip \
    && docker-php-ext-install intl opcache pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

FROM base AS development

ENV APP_ENV=dev

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]

FROM base AS production

ENV APP_ENV=prod

COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader

COPY . .
RUN php bin/console cache:warmup

USER www-data

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
