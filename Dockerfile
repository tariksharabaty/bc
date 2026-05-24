FROM php:8.2-apache

RUN apt-get update && apt-get install -y libpng-dev libzip-dev zip unzip git sqlite3
RUN docker-php-ext-install pdo pdo_mysql pdo_sqlite gd zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# Önce kilidi sil, sonra 8.2 ile uyumlu paketleri güncelle
RUN rm -rf vendor composer.lock && composer update --no-dev --optimize-autoloader

RUN mkdir -p database && touch database/database.sqlite
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

CMD php artisan migrate --force && apache2-foreground
