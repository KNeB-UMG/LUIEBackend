FROM php:8.3-fpm

# Install required dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libpng-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        gd \
        intl \
        mbstring \
        xml \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Symfony CLI
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash \
    && apt-get install -y symfony-cli \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/symfony

# Copy composer files first
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-scripts --no-autoloader

# Copy the rest of the application files
COPY . .

# Generate autoloader and run scripts
RUN composer dump-autoload --optimize && composer run-script post-install-cmd

# Ensure var directory exists and set permissions
RUN mkdir -p /var/www/symfony/var \
    && chown -R www-data:www-data /var/www/symfony \
    && chmod -R 775 /var/www/symfony/var

# Expose Symfony's default port
EXPOSE 8000

# Set the entrypoint
ENTRYPOINT ["symfony", "local:server:start", "--no-tls", "--port=8000", "--dir=/var/www/symfony", "--allow-http", "--allow-all-ip"]
