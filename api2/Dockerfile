FROM php:8.3-cli

# Install dependencies and extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && docker-php-ext-install pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files if present and install (won't fail if absent)
COPY composer.json composer.lock* ./
RUN if [ -f composer.json ]; then composer install --no-interaction --prefer-dist; fi

# Copy the rest of the application
COPY . .

EXPOSE 8080

CMD ["sh", "-lc", "php -S 0.0.0.0:8080 -t public"]
