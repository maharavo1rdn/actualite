FROM php:8.1-apache

# Installer les dépendances et l'extension pdo_mysql
RUN apt-get update && apt-get install -y --no-install-recommends \
    default-mysql-client \
    default-libmysqlclient-dev \
    libzip-dev \
    && docker-php-ext-install pdo_mysql zip \
    && docker-php-ext-enable pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite expires headers deflate

RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf
RUN echo 'AddDefaultCharset UTF-8' >> /etc/apache2/apache2.conf
RUN echo "AddType 'text/html; charset=UTF-8' .html .php" >> /etc/apache2/apache2.conf

RUN echo "default_charset = UTF-8" >> /usr/local/etc/php/php.ini
RUN echo "mbstring.internal_encoding = UTF-8" >> /usr/local/etc/php/php.ini

COPY . /var/www/html

# Permissions correctes
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80