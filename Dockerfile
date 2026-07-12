FROM php:8.3-fpm

ENV APP_ENV=dev XDEBUG_MODE=off

# ------------------------------------------------------------
# Dependências do sistema
# ------------------------------------------------------------
RUN apt-get update && apt-get install -y \
    git unzip curl libicu-dev libonig-dev libxml2-dev libzip-dev zip \
    && docker-php-ext-install intl mbstring pdo pdo_mysql zip opcache

# ------------------------------------------------------------
# Instalar Xdebug
# ------------------------------------------------------------
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Configuração do Xdebug
RUN echo "zend_extension=$(php-config --extension-dir)/xdebug.so" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.mode=debug,coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN pecl install redis \
    && docker-php-ext-enable redis

# ------------------------------------------------------------
# Instalar o Composer
# ------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ------------------------------------------------------------
# Instalar o Symfony CLI
# ------------------------------------------------------------
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# ------------------------------------------------------------
# Instalar PHPUnit global
# ------------------------------------------------------------
RUN composer global require phpunit/phpunit
ENV PATH="/root/.composer/vendor/bin:${PATH}"

# ------------------------------------------------------------
# Configuração do diretório de trabalho
# ------------------------------------------------------------
WORKDIR /var/www/html

# Expor porta do PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]
