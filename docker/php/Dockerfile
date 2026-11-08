# syntax=docker/dockerfile:1

ARG PHP_VERSION=8.5
FROM php:${PHP_VERSION}-fpm-bookworm

ARG UID=1000
ARG GID=1000

# Dependencias de compilación y de ejecución para las extensiones requeridas.
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq5 \
        libzip4 \
        libpng16-16 \
        libjpeg62-turbo \
        libfreetype6 \
        libicu72 \
        libsodium23; \
    apt-get install -y --no-install-recommends \
        libpq-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libicu-dev \
        libsodium-dev; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    # Sin -j: algunas extensiones (intl en PHP 8.5) fallan al instalar
    # módulos en paralelo dentro de la imagen oficial.
    docker-php-ext-install \
        pdo_pgsql \
        pgsql \
        bcmath \
        gd \
        zip \
        intl; \
    docker-php-ext-enable sodium; \
    apt-mark manual libpq5 libzip4 libpng16-16 libjpeg62-turbo libfreetype6 libicu72 libsodium23; \
    apt-get purge -y --auto-remove \
        libpq-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libicu-dev \
        libsodium-dev; \
    rm -rf /var/lib/apt/lists/* /tmp/pear

# Composer oficial.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Node 24 LTS para Vite.
COPY --from=node:24-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:24-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# Usuario sin privilegios con UID/GID configurables.
RUN set -eux; \
    groupadd --gid "${GID}" app; \
    useradd --uid "${UID}" --gid app --create-home --shell /bin/bash app; \
    mkdir -p /usr/local/var/run /var/www/html /home/app/.composer/cache; \
    chown -R app:app /usr/local/var/run /var/www/html /home/app

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html
USER app

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm", "-F"]
