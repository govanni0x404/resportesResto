# v1.20.0: imagen de DataRestoWeb para Docker (docker-compose.yml la arma con "docker compose up -d --build").
# Antes el contenedor instalaba pgsql en CADA arranque (apt-get en "command") y no tenía GD: las imágenes de
# productos (WebP) fallaban. Ahora se arma una sola vez con todo lo que usa el sistema.
FROM php:8.1-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" pgsql pdo_pgsql gd \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Hora de Chile y límites para subir imágenes de productos
RUN { \
      echo 'date.timezone = America/Santiago'; \
      echo 'upload_max_filesize = 16M'; \
      echo 'post_max_size = 20M'; \
      echo 'expose_php = Off'; \
    } > /usr/local/etc/php/conf.d/dataresto.ini

# Reglas de seguridad de Apache (.htaccess activos, sin .git ni respaldos a la vista)
COPY docker/apache-seguridad.conf /etc/apache2/conf-enabled/zz-seguridad.conf
