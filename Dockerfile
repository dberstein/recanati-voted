# FROM php:8.2.0-alpine
# RUN apk add composer libxml2 libxml2-dev
# RUN docker-php-ext-install mysqli pdo pdo_mysql \
#  && docker-php-ext-enable mysqli pdo pdo_mysql
FROM php:8.2-apache
RUN apt update -y && apt upgrade -y
RUN a2enmod rewrite && a2enmod actions

# Install composer requirements
RUN apt install -y libzip-dev/stable \
 && docker-php-ext-install zip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | \
    php -- --install-dir=/usr/local/bin --filename=composer

# Copy database skeleton
COPY ./src/voted.db /data/
RUN chmod -R 777 /data

# Run composer
WORKDIR /var/www
COPY composer-image.json ./composer.json
RUN composer update --no-dev && composer dumpautoload

# Copy source code
WORKDIR /var/www/html
COPY ./src/ ./

#--optimize-autoloader
# CMD ["php", "-S", "0.0.0.0:8080", "-t", "."]
