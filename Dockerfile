FROM php:8.1-apache

# Install dependencies needed for MySQL and common extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
        default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Copy application code into container
COPY . /var/www/html

# Ensure permissions are correct
RUN chown -R www-data:www-data /var/www/html

# DocumentRoot standard sur /var/www/html
RUN sed -ri 's!DocumentRoot /var/www/html/public!DocumentRoot /var/www/html!g' /etc/apache2/sites-available/*.conf || true && \
    sed -ri 's!<Directory /var/www/html/public/>!<Directory /var/www/html/>!g' /etc/apache2/apache2.conf || true && \
    sed -ri 's!<Directory /var/www/html>!<Directory /var/www/html/>!g' /etc/apache2/apache2.conf || true

EXPOSE 80

# Apache already starts by default in php:apache image
