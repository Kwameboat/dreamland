FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_pgsql pgsql pdo_mysql mysqli zip gd \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

COPY . .

RUN cp common/config/main-local.example.php common/config/main-local.php \
    && cp common/config/params-supabase.example.php common/config/params-local.php \
    && cp backend/config/main-local.example.php backend/config/main-local.php \
    && cp backend/config/params-local.example.php backend/config/params-local.php

RUN mkdir -p frontend/web/uploads api/runtime backend/runtime common/runtime \
    && chown -R www-data:www-data frontend/web/uploads api/runtime backend/runtime common/runtime

COPY backend/docker/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY backend/docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]
