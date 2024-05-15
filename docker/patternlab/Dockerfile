FROM php:8.1-alpine AS php_stage

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

FROM node:18-alpine AS node_stage

WORKDIR /app/patternlab

COPY ./patternlab/package.json .

RUN npm i

FROM php_stage as server

WORKDIR /app/patternlab

COPY --from=php_stage /app/patternlab /app/patternlab
COPY --from=node_stage /app/patternlab /app/patternlab

RUN apk --no-cache add nodejs npm

CMD ["sh"]