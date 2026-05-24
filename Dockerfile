FROM php:8.4-apache

# 1. Gerekli araçları kur
RUN apt-get update && apt-get install -y libpng-dev libzip-dev zip unzip git curl sqlite3 libsqlite3-dev
RUN docker-php-ext-install pdo pdo_mysql pdo_sqlite gd zip

# 2. Composer'ı kur
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Dosyaları kopyala
WORKDIR /var/www/html
COPY . .

# 4. Boş veritabanı dosyasını oluştur (İnşaat aşamasında hata vermemesi için)
RUN mkdir -p database && touch database/database.sqlite

# 5. Kütüphaneleri kur
RUN composer install --optimize-autoloader --no-dev

# 6. İzinleri ayarla
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

# 7. ÇOK KRİTİK: Site başlarken önce veritabanını kur, sonra sunucuyu aç
CMD php artisan migrate --force && apache2-foreground
