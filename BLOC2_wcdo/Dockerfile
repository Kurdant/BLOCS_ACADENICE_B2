  FROM php:8.2-apache

  RUN apt-get update \
      && apt-get install -y --no-install-recommends libpq-dev \
      && docker-php-ext-install pdo pdo_pgsql fileinfo \
      && apt-get clean \
      && rm -rf /var/lib/apt/lists/*

  RUN a2enmod rewrite headers
  
  COPY apache.conf /etc/apache2/sites-available/000-default.conf

  WORKDIR /var/www/html
  COPY app/ ./app/
  COPY public/ ./public/
  COPY routes/ ./routes/
  COPY config/ ./config/
  COPY database/ ./database/

  RUN mkdir -p storage/uploads && chown -R www-data:www-data /var/www/html

  EXPOSE 80
  CMD ["apache2-foreground"]