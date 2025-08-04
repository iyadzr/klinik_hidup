# Multi-stage build for production
FROM node:20-alpine AS node-builder

WORKDIR /app

# Copy package files first for better caching
COPY package.json package-lock.json* ./

# Install Node.js dependencies (including dev dependencies for build)
RUN npm ci --no-audit --no-fund && npm cache clean --force

# Copy source files needed for build
COPY webpack.config.js ./
COPY assets/ ./assets/
COPY public/ ./public/

# Set NODE_ENV to production and build frontend assets
ENV NODE_ENV=production
RUN npm run build

# Production PHP image - use PHP 8.3 for better performance and security
FROM php:8.3-fpm-alpine

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

# Copy built assets from node-builder stage
COPY --from=node-builder --chown=www:www /app/public/build ./public/build

# Run post-install scripts
RUN composer dump-autoload --optimize && composer clear-cache

# Create necessary directories and set proper permissions
RUN mkdir -p var/cache var/log public/uploads \
    && chown -R www:www var/ public/ \
    && chmod -R 755 var/ public/

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
