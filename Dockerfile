FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts

FROM php:8.3-apache
RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libsodium-dev libicu-dev libxml2-dev libsqlite3-dev libonig-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_sqlite zip gd sodium intl mbstring xml bcmath \
    && a2enmod rewrite headers \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
# Volume sipersu_db menimpa folder database/ saat dijalankan: simpan salinan migrasi/seeder dari image
# agar entrypoint dapat menyegarkannya (jika tidak, migrasi baru tidak pernah terlihat setelah image dibangun ulang).
RUN mkdir -p /opt/database-dist && cp -r database/migrations database/seeders database/factories /opt/database-dist/ \
    && sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p storage/keys storage/logs storage/framework/{cache,sessions,views} bootstrap/cache database \
    && chown -R www-data:www-data storage bootstrap/cache database
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
