  FROM php:8.3-fpm-alpine

  RUN apk add --no-cache bash git unzip icu-dev libzip-dev postgresql-dev oniguruma-dev \
      && docker-php-ext-install pdo pdo_pgsql pgsql intl zip bcmath \
      && rm -rf /var/cache/apk/*

  COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

  WORKDIR /var/www/html
  
  COPY composer.json composer.lock ./
  RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

  COPY . .

  RUN php artisan package:discover --ansi || true \
      && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/testing storage/framework/views storage/logs bootstrap/cache \
      && chown -R www-data:www-data storage bootstrap/cache \
      && chmod -R ug+rwX storage bootstrap/cache

  EXPOSE 9000
  CMD ["php-fpm"]