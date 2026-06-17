FROM composer:latest as builder

WORKDIR /app

# Copy app structure needed for composer scripts and Tailwind build
COPY app/composer.json app/composer.lock /app/
COPY app/bin /app/bin
COPY app/config /app/config
COPY app/public /app/public
COPY app/src /app/src
COPY app/templates /app/templates
COPY app/assets /app/assets

# Install without post-install scripts (cache:clear needs .env which isn't available during build)
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Build Tailwind CSS for production (templates needed for class scanning)
RUN APP_ENV=prod bin/console tailwind:build --minify

FROM dunglas/frankenphp:latest

WORKDIR /app

RUN install-php-extensions \
    intl \
    mysqli \
    pdo_mysql \
    redis

# Copy vendor from builder
COPY --from=builder /app/vendor /app/vendor

# Copy pre-built Tailwind CSS from builder (fixes missing CSS in production)
COPY --from=builder /app/var/tailwind /app/var/tailwind

# Copy app files
COPY app /app

ENTRYPOINT ["frankenphp"]
CMD ["run", "--config", "/app/Caddyfile"]
