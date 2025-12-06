FROM php:8.4-cli

ENV APP_ENV=prod

ENV DATABASE_URL="postgresql://pgsql:reg45y54g545gdpimgrhA%@172.27.240.3:5432/app?serverVersion=16&charset=utf8"

ENV MESSENGER_TRANSPORT_DSN=doctrine://default

RUN apt-get update && apt-get install -y \
    git unzip libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-scripts

CMD ["php", "bin/console", "messenger:consume", "async", "-vv", "--limit=500"]
