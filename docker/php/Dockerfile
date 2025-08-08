# Multi-stage build for production
FROM node:20-alpine AS node-builder

WORKDIR /app

# Set npm configuration for better network handling in production builds
RUN npm config set registry https://registry.npmjs.org/ && \
    npm config set fetch-retries 5 && \
    npm config set fetch-retry-mintimeout 20000 && \
    npm config set fetch-retry-maxtimeout 120000 && \
    npm config set fetch-timeout 300000

# Copy package files first for better caching
COPY package.json package-lock.json* ./

# Install Node.js dependencies with retry logic and fallbacks
RUN npm ci --no-audit --no-fund && npm cache clean --force || \
    (npm cache clean --force && npm ci --no-audit --no-fund) || \
    (rm -rf node_modules package-lock.json && npm install --no-audit --no-fund && npm cache clean --force)

# Copy source files needed for build
COPY webpack.config.js ./
COPY assets/ ./assets/
COPY public/ ./public/

# Set NODE_ENV to production and build frontend assets
ENV NODE_ENV=production
RUN npm run build

# Production PHP image - use PHP 8.2 for stability and compatibility
FROM php:8.2-fpm-alpine3.19

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    tzdata \
    bash \
    mysql-client \
    && rm -rf /var/cache/apk/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd opcache

# Set timezone
ENV TZ=Asia/Kuala_Lumpur
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Configure PHP for production
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Copy PHP-FPM configuration
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Get latest Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Create app user
RUN addgroup -g 1000 www && adduser -u 1000 -G www -s /bin/bash -D www

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for better caching
COPY --chown=www:www composer.json composer.lock ./

# Install Composer dependencies as root first
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --no-plugins

# Copy application files with proper ownership
COPY --chown=www:www . .

# Copy built assets from node-builder stage (fallback assets)
COPY --from=node-builder --chown=www:www /app/public/build ./public/build

# Copy asset loader for dynamic asset management
COPY --chown=www:www docker/php/asset-loader.php ./src/Utils/AssetLoader.php

# Run post-install scripts
RUN composer dump-autoload --optimize && composer clear-cache

# Create necessary directories and set proper permissions
RUN mkdir -p var/cache var/log public/uploads config/jwt \
    && chown -R www:www var/ public/ config/jwt \
    && chmod -R 755 var/ public/

# Generate JWT keys during build (production-safe approach)
RUN openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 -pass pass:0769fa69cb42c84beedcfc421bd5ff638be91715fa4987b71afd2dd1a845077a \
    && openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout -passin pass:0769fa69cb42c84beedcfc421bd5ff638be91715fa4987b71afd2dd1a845077a \
    && chown www:www config/jwt/private.pem config/jwt/public.pem \
    && chmod 600 config/jwt/private.pem \
    && chmod 644 config/jwt/public.pem

# Copy and set up startup scripts
COPY docker/php/startup.sh /usr/local/bin/startup.sh
COPY docker/php/startup-prod.sh /usr/local/bin/startup-prod.sh
RUN chmod +x /usr/local/bin/startup.sh /usr/local/bin/startup-prod.sh

# Create health check script
COPY docker/php/healthcheck.sh /usr/local/bin/healthcheck.sh
RUN chmod +x /usr/local/bin/healthcheck.sh

# Switch to non-root user
USER www

# Add health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD /usr/local/bin/healthcheck.sh

# Use startup script as entrypoint
ENTRYPOINT ["/usr/local/bin/startup.sh"]

# Expose port 9000 for PHP-FPM
EXPOSE 9000
