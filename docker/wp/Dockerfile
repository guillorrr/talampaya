ARG WORDPRESS_VERSION

FROM wordpress:${WORDPRESS_VERSION}

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
    vim nano

# Install locales
RUN apt-get install -y locales
RUN sed -i '/^#.* es_ES.* /s/^#//' /etc/locale.gen
RUN sed -i '/^#.* ca_ES.* /s/^#//' /etc/locale.gen
RUN locale-gen

# Install xdebug
RUN pecl install xdebug-3.3.1 \
	&& docker-php-ext-enable xdebug
RUN usermod -u 1000 www-data && groupmod -o -g 1000 www-data

# Install wp-cli
RUN apt-get update && apt-get install -y sudo less mariadb-client
RUN curl -o /bin/wp-cli.phar https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
RUN chmod +x /bin/wp-cli.phar
RUN cd /bin && mv wp-cli.phar wp
RUN mkdir -p /var/www/.wp-cli/cache && chown www-data:www-data /var/www/.wp-cli/cache

# Forward Message to mailhog
RUN curl --location --output /usr/local/bin/mhsendmail https://github.com/mailhog/mhsendmail/releases/download/v0.2.0/mhsendmail_linux_amd64 && \
    chmod +x /usr/local/bin/mhsendmail
RUN echo 'sendmail_path="/usr/local/bin/mhsendmail --smtp-addr=mailhog:1025 --from=no-reply@wp.lo"' > /usr/local/etc/php/conf.d/mailhog.ini

# Note: Use docker-compose up -d --force-recreate --build when Dockerfile has changed.