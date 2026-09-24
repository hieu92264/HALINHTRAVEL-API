FROM php:8.2-fpm-alpine

# install dependencies
RUN apk add --no-cache \
    $PHPIZE_DEPS \
    git \
    curl \
    zip \
    unzip \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    sqlite-dev \
    nodejs \
    npm

# install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    bcmath \
    intl \
    zip \
    opcache \
    gd \
    pdo_sqlite \
    xml \
    pcntl

# Laravel uses phpredis as configured in .env.example.
RUN pecl install redis \
    && docker-php-ext-enable redis

# get composer from the official composer image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# set working directory
WORKDIR /var/www/html

# run init
CMD ["php-fpm"]
