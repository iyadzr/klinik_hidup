# Multi-stage build for production
FROM node:18-alpine AS node-builder

WORKDIR /app

# Copy package files
COPY package.json ./
COPY package-lock.json* ./

# Install Node.js dependencies (including dev dependencies for build)
RUN npm ci && npm cache clean --force

# Copy source files needed for build
COPY webpack.config.js ./
COPY assets/ ./assets/
COPY public/ ./public/

# Set NODE_ENV to production and build frontend assets
ENV NODE_ENV=production
RUN npm run build

# Production PHP image
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    tzdata \
    supervisor \
    nginx \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd opcache

# Set timezone
ENV TZ=Asia/Kuala_Lumpur
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone
RUN echo "date.timezone = Asia/Kuala_Lumpur" > /usr/local/etc/php/conf.d/timezone.ini

# Configure PHP for production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.save_comments=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini

# Copy PHP-FPM configuration
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create app user
RUN groupadd -g 1000 www && useradd -u 1000 -ms /bin/bash -g www www

# Set working directory
WORKDIR /var/www/html

# Copy composer files first
COPY composer.json composer.lock* ./

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
    && composer dump-autoload --optimize \
    && composer clear-cache

# Copy application files with proper ownership
COPY --chown=www:www . .

# Copy built assets from node-builder stage (overwrite any existing dev assets)
COPY --from=node-builder --chown=www:www /app/public/build ./public/build

# Copy startup script and make it executable
COPY docker/php/startup.sh /usr/local/bin/startup.sh
RUN chmod +x /usr/local/bin/startup.sh

# Create necessary directories and set proper permissions
RUN mkdir -p /var/www/html/var/cache \
    && mkdir -p /var/www/html/var/log \
    && mkdir -p /var/www/html/public/uploads \
    && chown -R www:www /var/www/html \
    && find /var/www/html/var -type d -exec chmod 755 {} \; \
    && find /var/www/html/public -type d -exec chmod 755 {} \; \
    && find /var/www/html -name "*.php" -type f -exec chmod 644 {} \;

# Switch to non-root user
USER www

# Use startup script as entrypoint
CMD ["/usr/local/bin/startup.sh"]

# Expose port 9000 for PHP-FPM
EXPOSE 9000
