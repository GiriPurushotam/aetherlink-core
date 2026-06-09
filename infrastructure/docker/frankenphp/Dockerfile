# ==============================================================================
# Base Image Selection (PHP 8.4 + FrankenPHP Alpine)
# Alpine Linux ensures the smallest security attack surface and lowest image size.
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
# High-Performance PHP Extension Installation via Official Installer
# ==============================================================================
RUN install-php-extensions \
    opcache \
    pcntl \
    pdo_pgsql \
    redis \
    intl \
    zip

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
# Security & Process Engineering: Non-Root Execution
# Container processes should never run as root in production environments.
# ==============================================================================
RUN adduser -D -u 1000 -g 1000 aether_worker

# Establish the standard workspace directory
WORKDIR /app

# Give our worker permission over the execution workspace
RUN chown -R aether_worker:aether_worker /app

USER aether_worker