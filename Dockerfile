FROM php:8.2-cli

WORKDIR /var/www/html

RUN apt-get update \
  && apt-get install -y --no-install-recommends git unzip libzip-dev \
  && docker-php-ext-install zip \
  && rm -rf /var/lib/apt/lists/*

COPY . .

RUN chown -R www-data:www-data /var/www/html \
  && find /var/www/html/storage -type d -exec chmod 775 {} + \
  && find /var/www/html/storage -type f -exec chmod 664 {} +

EXPOSE 8080
ENV PORT=8080

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT} -t /var/www/html"]
