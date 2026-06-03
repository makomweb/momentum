FROM dunglas/frankenphp:latest

WORKDIR /app

RUN install-php-extensions \
    intl \
    mysqli \
    pdo_mysql \
    redis

COPY app /app

ENTRYPOINT ["frankenphp"]
CMD ["run", "--config", "/app/Caddyfile"]
