FROM php:8.1-alpine

RUN apk --no-cache add \
    zip \
    unzip \
    git \
    libzip-dev \
    autoconf \
    g++ \
    make \
    linux-headers

WORKDIR /app/patternlab

RUN apk --no-cache add nodejs npm

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

CMD ["sh"]
