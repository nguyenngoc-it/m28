FROM ghcr.io/gobiz-vinasat/image-php-fpm:7.4.1
COPY --chown=www-data:www-data . /var/www/html
