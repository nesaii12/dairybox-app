# ==========================================
# Stage 1: Build Frontend Assets (Vite)
# ==========================================
FROM node:20-alpine AS node-builder

WORKDIR /app

# Copy dependency manifests first for build caching
COPY package*.json ./
RUN npm ci --legacy-peer-deps || npm install

# Copy source assets required by Vite and Tailwind CSS
COPY vite.config.js ./
COPY resources/ ./resources/
COPY app/ ./app/
COPY public/ ./public/

# Compile production bundles into public/build
RUN npm run build

# ==========================================
# Stage 2: Production PHP + Apache Application
# ==========================================
FROM php:8.2-apache

WORKDIR /var/www/html

# Prevent interactive prompts during apt-get
ENV DEBIAN_FRONTEND=noninteractive

# Install system libraries and PHP extensions needed for Laravel, Excel, and QR codes
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libpq-dev \
    libsqlite3-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        pdo_sqlite \
        pdo_pgsql \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && a2enmod rewrite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy composer manifests first for Docker layer caching
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Copy the rest of the application codebase
COPY . .

# Copy compiled frontend assets from Stage 1
COPY --from=node-builder /app/public/build ./public/build

# Generate optimized Composer autoloader
RUN composer dump-autoload --optimize --no-dev

# Copy Apache configuration, PHP settings, and entrypoint script
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom-php.ini
COPY docker/entrypoint.sh /entrypoint.sh

# Ensure Linux line endings (prevent CRLF issues from Windows git) and set permissions
RUN sed -i 's/\r$//' /entrypoint.sh && chmod +x /entrypoint.sh

# Set directory ownership and permissions for Laravel writable directories
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Render uses dynamic $PORT (usually 10000 or 80)
EXPOSE 80 10000

# Run entrypoint script
ENTRYPOINT ["/entrypoint.sh"]
