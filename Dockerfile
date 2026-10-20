FROM php:8.1-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        default-mysql-client \
        libonig-dev \
    && docker-php-ext-install mbstring pdo_mysql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Match the upload limits declared in public/.htaccess.
RUN printf "upload_max_filesize=32M\npost_max_size=96M\nmemory_limit=256M\n" \
    > /usr/local/etc/php/conf.d/uploads.ini

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
