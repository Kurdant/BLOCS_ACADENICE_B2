  FROM php:8.2-apache

  RUN apt-get update \
      && apt-get install -y --no-install-recommends libpq-dev \
      && docker-php-ext-install pdo pdo_pgsql fileinfo \
      && apt-get clean \
      && rm -rf /var/lib/apt/lists/*

  RUN a2enmod rewrite headers

  COPY apache.conf /etc/apache2/sites-available/000-default.conf

  WORKDIR /var/www/html
  
  # Code applicatif (sources copiées dans l'image en prod)
  COPY app/ /var/www/html/app/
  COPY public/ /var/www/html/public/
  COPY routes/ /var/www/html/routes/
  COPY config/ /var/www/html/config/
  COPY database/ /var/www/html/database/

  RUN mkdir -p /var/www/html/storage/uploads \
      && chown -R www-data:www-data /var/www/html

  EXPOSE 80

  CMD ["apache2-foreground"]

