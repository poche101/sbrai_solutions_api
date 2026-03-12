# PHP stage
FROM php:8.2-fpm-alpine

# Install system dependencies and PHP extension dependencies
RUN apk add --no-cache \
    curl \
    git \
    mysql-client \
    zip \
    unzip \
    gettext \
    oniguruma-dev \
    libzip-dev \
    libxml2-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev

# Install PHP extensions in a specific order with proper configuration
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    bcmath \
    ctype \
    fileinfo \
    mbstring \
    mysqli \
    pdo \
    pdo_mysql \
    tokenizer \
    xml \
    zip \
    gd \
    && docker-php-ext-enable \
    bcmath \
    ctype \
    fileinfo \
    mbstring \
    mysqli \
    pdo \
    pdo_mysql \
    tokenizer \
    xml \
    zip \
    gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy application code
COPY --chown=www-data:www-data . .

# Install PHP dependencies
RUN composer install --no-dev --no-interaction --optimize-autoloader

# Create necessary directories and set permissions
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views \
    && chown -R www-data:www-data /app \
    && chmod -R 755 storage bootstrap/cache

# Expose port (Render uses PORT env variable)
EXPOSE 8000

# Set environment to production
ENV APP_ENV=production
ENV LARAVEL_MOTTO="Build something amazing."

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -f http://localhost:8000/health || exit 1

# Use a custom startup script to handle migrations and server start
RUN echo '#!/bin/sh\n\
php artisan config:cache\n\
php artisan route:cache\n\
php artisan migrate --force || true\n\
php -S 0.0.0.0:${PORT:-8000} -t public' > /app/start.sh && chmod +x /app/start.sh

# Run the application
CMD ["/app/start.sh"]