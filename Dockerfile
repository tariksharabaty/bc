FROM php:8.2-apache

# Gerekli kütüphaneleri kur
RUN apt-get update && apt-get install -y libpng-dev libzip-dev zip unzip

# PDO ve diğer eklentileri kur
RUN docker-php-ext-install pdo pdo_mysql gd zip

# Apache'nin DocumentRoot'unu /var/www/html/public olarak değiştir
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Rewrite modunu aç (Laravel rotaları için şart)
RUN a2enmod rewrite

# Dosyaları kopyala
COPY . /var/www/html
WORKDIR /var/www/html

# İzinleri ayarla
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
