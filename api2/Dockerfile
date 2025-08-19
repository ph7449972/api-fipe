FROM php:8.2-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev libicu-dev git unzip \
    && docker-php-ext-install pdo pdo_mysql intl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock* ./
RUN composer install --no-interaction --no-progress --prefer-dist

COPY . .

CMD ["php", "worker.php"]
