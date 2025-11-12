ARG WORDPRESS_VERSION=php8.4-fpm-alpine

FROM wordpress:${WORDPRESS_VERSION}

# Install dependencies and tools
RUN apk update && apk add --no-cache \
    vim \
    nano \
    less \
    mariadb-client \
    sudo \
    musl-locales \
    musl-locales-lang \
    shadow \
    && rm -rf /var/cache/apk/*

# Configure locales
ENV LANG=es_ES.UTF-8
ENV LANGUAGE=es_ES:ca_ES
ENV LC_ALL=es_ES.UTF-8
RUN echo "es_ES.UTF-8 UTF-8" >> /etc/locale.gen \
    && echo "ca_ES.UTF-8 UTF-8" >> /etc/locale.gen

# Install Xdebug
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS autoconf g++ make linux-headers \
    && pecl install xdebug-3.4.2 \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps \
    && rm -rf /tmp/pear

# Set user and group
RUN usermod -u 1000 www-data && groupmod -o -g 1000 www-data

# Install WP-CLI
RUN curl -o /bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x /bin/wp \
    && mkdir -p /var/www/.wp-cli/cache \
    && chown www-data:www-data /var/www/.wp-cli/cache

# Install mhsendmail for Mailhog
RUN curl -L -o /usr/local/bin/mhsendmail https://github.com/mailhog/mhsendmail/releases/download/v0.2.0/mhsendmail_linux_amd64 \
    && chmod +x /usr/local/bin/mhsendmail \
    && echo 'sendmail_path="/usr/local/bin/mhsendmail --smtp-addr=mailhog:1025 --from=no-reply@wp.lo"' > /usr/local/etc/php/conf.d/mailhog.ini

CMD ["php-fpm"]
