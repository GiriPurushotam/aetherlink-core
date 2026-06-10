# ==============================================================================
# Base Image Selection (PHP 8.4 + FrankenPHP Alpine)
# ==============================================================================
FROM dunglas/frankenphp:1.4-php8.4-alpine AS base

# ==============================================================================
# System Dependencies & Core Build Tools
# ==============================================================================
RUN apk add --no-cache \
    bash \
    git \
    unzip \
    linux-headers

# ==============================================================================
# High-Performance PHP Extensions
# ==============================================================================
RUN install-php-extensions \
    opcache \
    pcntl \
    pdo_pgsql \
    redis \
    intl \
    zip

# ==============================================================================
# Pro-Grade Multi-Stage Binary Ingestion: Install Composer Natively
# ==============================================================================
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# ==============================================================================
# Production-Grade PHP/OpCache Configuration Layer
# ==============================================================================
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Inject optimized OpCache settings directly into the runtime configuration
RUN { \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.enable_cli=1'; \
    } > $PHP_INI_DIR/conf.d/opcache-optimized.ini

# ==============================================================================
# Security & Process Engineering: Non-Root Execution Setup
# ==============================================================================
RUN adduser -D -u 1000 -g 1000 aether_worker

# Establish the standard workspace directory
WORKDIR /app

# Pre-forge Caddy's system storage directories and bind ownership to the worker
RUN mkdir -p /data/caddy /config/caddy

# Give our worker explicit permissions over execution and system state folders
RUN chown -R aether_worker:aether_worker /app /data /config

# Define the high-performance public web entrypoint context natively
ENV PUBLIC_WEB_ROOT=/app/public

USER aether_worker