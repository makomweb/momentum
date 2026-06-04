FROM composer:latest as builder

WORKDIR /app

# Copy app structure needed for composer scripts
COPY app/composer.json app/composer.lock /app/
COPY app/bin /app/bin
COPY app/config /app/config
COPY app/public /app/public
COPY app/src /app/src

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

FROM dunglas/frankenphp:latest

WORKDIR /app

RUN install-php-extensions \
    intl \
    mysqli \
    pdo_mysql \
    redis

# Copy vendor from builder
COPY --from=builder /app/vendor /app/vendor

# Copy app
COPY app /app

ENTRYPOINT ["frankenphp"]
CMD ["run", "--config", "/app/Caddyfile"]
