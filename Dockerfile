FROM dunglas/frankenphp:php8.4-alpine

# Переменные окружения для продакшена
ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV SERVER_NAME=":8080"
ENV DATABASE_URL="postgresql://pgsql:reg45y54g545gdpimgrhA%@172.27.240.3:5432/app?serverVersion=16&charset=utf8"
ENV MAILER_DSN=""
ENV FRANKENPHP_DOCUMENT_ROOT="/app/public"
ENV APP_URL="https://task4-761966872328.europe-west1.run.app"

RUN install-php-extensions \
    intl \
    pdo_mysql \
    pdo_pgsql \
    zip \
    opcache \
    apcu

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY ./opcache.ini $PHP_INI_DIR/conf.d/opcache.ini
COPY ./Caddyfile /etc/caddy/Caddyfile

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY ./composer.json ./composer.lock ./symfony.lock ./

WORKDIR /app

COPY . .

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-progress \
    --optimize-autoloader

RUN composer dump-autoload --optimize --classmap-authoritative --no-dev
RUN set -eux; \
    mkdir -p var/cache var/log; \
    chmod -R 777 var;

RUN chmod +x entrypoint.sh

VOLUME [ "/app" ]
# VOLUME [ "caddy_data:/data" ]
# VOLUME [ "caddy_config:/config" ]

EXPOSE 8080
ENTRYPOINT ["/app/entrypoint.sh"]