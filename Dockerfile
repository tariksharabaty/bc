FROM php:8.2-apache

# Gerekli kütüphaneleri kur
RUN apt-get update && apt-get install -y libpng-dev libzip-dev zip unzip git sqlite3
RUN docker-php-ext-install pdo pdo_mysql pdo_sqlite gd zip

# Composer'ı kur
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Çalışma dizini
WORKDIR /var/www/html
COPY . .

# 1. Kilidi siliyoruz (ÇOK ÖNEMLİ)
# 2. --ignore-platform-reqs ile sürüm çakışmalarını tamamen devre dışı bırakıyoruz
RUN rm -f composer.lock && composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Veritabanı ve izinler
RUN mkdir -p database && touch database/database.sqlite
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

CMD php artisan migrate --force && apache2-foreground
