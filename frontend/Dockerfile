FROM php:8.1-apache

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libonig-dev \
    && docker-php-ext-install intl pdo_mysql zip mbstring \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY frontend/ ./

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --no-scripts

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf /etc/apache2/apache2.conf

RUN chown -R www-data:www-data storage bootstrap/cache && \
    find storage -type d -exec chmod 775 {} \; && \
    find storage -type f -exec chmod 664 {} \; && \
    chmod -R 775 bootstrap/cache

EXPOSE 80