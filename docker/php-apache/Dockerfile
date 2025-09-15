FROM php:8.3-apache

# Extensiones necesarias mínimas
RUN apt-get update && apt-get install -y \
        git unzip libicu-dev libonig-dev libzip-dev \
    && docker-php-ext-install pdo_mysql intl opcache \
    && a2enmod rewrite

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configurar DocumentRoot a /public (lo define env o lo forzamos en vhost)
ARG DOCROOT=/var/www/html/public
ENV APACHE_DOCUMENT_ROOT=${DOCROOT}

# VirtualHost con AllowOverride para .htaccess (apache-pack)
COPY vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
