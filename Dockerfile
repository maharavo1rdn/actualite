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

EXPOSE 80

# Apache already starts by default in php:apache image
