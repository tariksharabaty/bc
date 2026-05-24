FROM php:8.2-apache
RUN apt-get update && apt-get install -y libpng-dev libzip-dev zip unzip git
RUN docker-php-ext-install pdo pdo_mysql gd zip
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN cd /var/www/html && composer install --no-dev --optimize-autoloader
