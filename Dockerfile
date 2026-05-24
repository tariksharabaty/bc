FROM php:8.2-apache
RUN apt-get update && apt-get install -y libpng-dev libzip-dev zip unzip
RUN docker-php-ext-install pdo pdo_mysql gd zip
COPY . /var/www/html
WORKDIR /var/www/html
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN a2enmod rewrite
