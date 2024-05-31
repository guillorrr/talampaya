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

CMD ["sh"]
