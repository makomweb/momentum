FROM dunglas/frankenphp:latest

WORKDIR /app

RUN install-php-extensions \
    pdo_mysql \
    mysqli

COPY app /app

ENTRYPOINT ["frankenphp"]
CMD ["run", "--config", "/app/Caddyfile"]
