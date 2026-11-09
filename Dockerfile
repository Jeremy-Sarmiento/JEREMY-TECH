# Imagen de producción para Render: PHP 8.2 + Apache con PostgreSQL.
# El código se sirve desde public/; el resto del proyecto queda fuera del
# DocumentRoot. No instala Composer: la app no usa vendor/ en producción.
FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev imagemagick \
    && docker-php-ext-install pdo_pgsql \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . .

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-jeremy-tech.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Render inyecta PORT en tiempo de ejecución; el entrypoint lo aplica.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
