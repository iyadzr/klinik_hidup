# Multi-stage build for production
FROM node:18-alpine AS node-builder

WORKDIR /app

# Copy package files
COPY package.json ./
COPY package-lock.json* ./

# Install Node.js dependencies (including dev dependencies for build)
RUN npm ci && npm cache clean --force

# Copy source files needed for build
COPY . .

# Build frontend assets
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

# Install Node.js and npm
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

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

# Copy package.json for runtime dependencies
COPY package.json package-lock.json* ./

# Install Node.js dependencies for runtime (production only)
RUN npm ci --only=production \
    && npm cache clean --force

# Copy application files with proper ownership
COPY --chown=www:www . .

# Copy built assets from node-builder stage
COPY --from=node-builder --chown=www:www /app/public/build ./public/build

# Set proper permissions
RUN chown -R www:www /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache \
    && chmod -R 755 /var/www/html/var \
    && chmod -R 755 /var/www/html/public

# Switch to non-root user
USER www

# Expose port 9000 for PHP-FPM
EXPOSE 9000
