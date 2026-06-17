FROM composer:latest AS builder

WORKDIR /app

# Copy app structure needed for composer scripts and asset builds
COPY app/composer.json app/composer.lock /app/
COPY app/bin /app/bin
COPY app/config /app/config
COPY app/public /app/public
COPY app/src /app/src
COPY app/templates /app/templates
COPY app/assets /app/assets

# Install dependencies without post-install scripts
# (cache:clear needs .env which isn't available during build)
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Compile Asset Mapper (generates hashed CSS/JS files in public/assets/)
RUN APP_ENV=prod bin/console asset-map:compile

# Build Tailwind CSS for production (templates needed for class scanning)
RUN APP_ENV=prod bin/console tailwind:build --minify

FROM dunglas/frankenphp:latest

WORKDIR /app

RUN install-php-extensions \
    intl \
    mysqli \
    pdo_mysql \
    redis

# Copy vendor from builder (compiled dependencies)
COPY --from=builder /app/vendor /app/vendor

# Copy pre-built assets from builder (hashed CSS/JS filenames)
COPY --from=builder /app/public/assets /app/public/assets

# Copy pre-built Tailwind CSS from builder (used by TailwindCssAssetCompiler)
COPY --from=builder /app/var/tailwind /app/var/tailwind

# Copy app files (but not .env - it's gitignored and provided via env vars in production)
COPY app /app

ENTRYPOINT ["frankenphp"]
CMD ["run", "--config", "/app/Caddyfile"]
