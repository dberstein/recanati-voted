FROM php:8.2-apache
RUN apt update -y && apt upgrade -y \
 && a2enmod rewrite && a2enmod actions \
# Install composer requirements \
 && apt install -y libzip-dev/stable \
 && docker-php-ext-install zip \
# Configure PHP \
 && mv /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini \
 && sed -i'' 's/expose_php = On/expose_php = Off/g' /usr/local/etc/php/php.ini

# Install Composer
RUN curl -sS https://getcomposer.org/installer | \
    php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/

# Run composer (code must autoload using PSR-4)
COPY ./composer-image.json ./composer.json
RUN composer update --no-dev && composer dumpautoload

# Copy source code
COPY ./src/ ./

# Copy database skeleton
COPY ./src/voted.db /data/
RUN chmod -R 777 /data
