FROM dunglas/frankenphp:latest as builder

WORKDIR /app

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy composer files
COPY app/composer.json app/composer.lock /app/

# Install dependencies
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

---

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
