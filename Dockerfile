FROM php:8.2-apache

# Gerekli araçları kur
RUN apt-get update && apt-get install -y libpng-dev libzip-dev zip unzip git curl

# PHP eklentilerini kur
RUN docker-php-ext-install pdo pdo_mysql gd zip

# Composer'ın en güncel ve stabil versiyonunu çek
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Çalışma klasörünü ayarla ve kodları kopyala
WORKDIR /var/www/html
COPY . .

# Kütüphaneleri kur (Çakışmaları görmezden gelerek)
RUN composer install --optimize-autoloader --no-dev --ignore-platform-reqs

# İzinleri ve klasör yönlendirmesini ayarla
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite
