FROM php:8.3-fpm


RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libpng-dev \
    && docker-php-ext-install pdo pdo_mysql gd

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Symfony CLI
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash \
    && apt-get install symfony-cli

WORKDIR /var/www/symfony/

EXPOSE 8000

