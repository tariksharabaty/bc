FROM php:8.4-apache

# 1. Gerekli araçları ve SQLite kütüphanelerini kur
RUN apt-get update && apt-get install -y libpng-dev libzip-dev zip unzip git curl sqlite3 libsqlite3-dev

# 2. PHP eklentilerini kur (SQLite desteği eklendi)
RUN docker-php-ext-install pdo pdo_mysql pdo_sqlite gd zip

# 3. Composer'ı kur
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Çalışma klasörünü ayarla ve kodları kopyala
WORKDIR /var/www/html
COPY . .

# 5. Kütüphaneleri kur (Artık sürüm uyumlu olduğu için ignore etmeye gerek yok)
RUN composer install --optimize-autoloader --no-dev

# 6. ÇOK KRİTİK: Veritabanını imajın içine inşa et (Render engelini aşıyoruz)
ENV DB_CONNECTION=sqlite
ENV DB_DATABASE=/var/www/html/database/database.sqlite
RUN touch database/database.sqlite
RUN php artisan migrate --force

# 7. İzinleri ve klasör yönlendirmesini ayarla (Database klasörüne de yazma izni veriyoruz)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite
