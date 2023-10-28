FROM node:18 AS webpack

WORKDIR /app

COPY package.json package-lock.json /app/
RUN npm install

COPY webpack.config.js tsconfig.json /app/
COPY src/main/resources /app/src/main/resources
RUN npm run build


FROM composer AS composer

COPY composer.* /app/

WORKDIR /app

RUN composer install --no-dev --ignore-platform-reqs && \
    rm /app/composer.json /app/composer.lock


FROM ghcr.io/programie/php-docker

ENV WEB_ROOT=/app/public

RUN install-php 8.2 curl dom intl pdo-mysql && \
    a2enmod rewrite && \
    mkdir -p /app/var && \
    chown www-data: /app/var

ENV PATH="${PATH}:/app/bin"
WORKDIR /app

COPY --from=composer /app/vendor /app/vendor
COPY --from=webpack /app/public/assets /app/public/assets
COPY --from=webpack /app/webpack.assets.json /app/webpack.assets.json

COPY bin /app/bin
COPY config /app/config
COPY public /app/public
COPY src /app/src
COPY templates /app/templates
COPY .env /app