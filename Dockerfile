FROM php:8.0-apache

# System deps
RUN apt-get update && apt-get install -y \
    git zip unzip curl libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip

# Enable apache rewrite
RUN a2enmod rewrite

WORKDIR /var/www/html

# Copy composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy source
COPY . .

# Cài vendor
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# Quyền thư mục
RUN chown -R www-data:www-data storage bootstrap/cache

# Apache vhost
COPY .docker/vhost.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80
