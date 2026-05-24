FROM php:8.2-apache

# 1. Tüm sistem bağımlılıklarını kur (GD, ZIP vb. için gerekli olanlar)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql pdo_sqlite zip

# 2. Composer'ı kur
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Klasör yapısını ayarla
WORKDIR /var/www/html
COPY . .

# 4. Bağımlılıkları güncelle ve temizle
RUN rm -rf vendor composer.lock && composer update --no-dev --no-scripts --optimize-autoloader

# 5. İzinler ve Apache ayarları
RUN mkdir -p database && touch database/database.sqlite
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

CMD php artisan migrate --force && apache2-foreground
