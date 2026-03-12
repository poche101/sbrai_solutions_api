FROM php:8.2-cli

# Set working directory
WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    libzip-dev \
    libxml2-dev \
    libonig-dev \
    libicu-dev \
    mysql-client \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    zip \
    xml \
    bcmath \
    ctype \
    fileinfo \
    tokenizer

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy application files
COPY . /app

# Set permissions
RUN chown -R www-data:www-data /app \
    && chmod -R 755 storage bootstrap/cache

# Install PHP dependencies (no dev dependencies)
RUN composer install --no-dev --no-interaction --optimize-autoloader

# Create Laravel required directories
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views

# Expose port
EXPOSE 8000

# Create startup script
RUN echo '#!/bin/sh\n\
php artisan config:cache\n\
php artisan route:cache\n\
php -S 0.0.0.0:${PORT:-8000} -t public' > /app/start.sh && chmod +x /app/start.sh

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -f http://localhost:${PORT:-8000}/health || exit 1

# Run the application
CMD ["/app/start.sh"]