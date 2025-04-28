# Use the official PHP 8.4 FPM image
FROM php:8.4-fpm-alpine

# Set working directory
WORKDIR /var/www/html

# Install system dependencies AND bash
RUN apk update && apk add --no-cache \
    build-base \
    mysql-client \
    libzip-dev \
    zip \
    bash \
    linux-headers \
    # Add other dependencies if needed
    && docker-php-ext-install pdo pdo_mysql zip

# Install Xdebug
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

# Configure Xdebug
RUN echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.idekey=PHPSTORM" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"] 