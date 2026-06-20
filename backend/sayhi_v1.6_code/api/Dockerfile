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
    && a2enmod rewrite headers alias \
    && rm -rf /var/lib/apt/lists/*

RUN { \
    echo "upload_max_filesize=128M"; \
    echo "post_max_size=128M"; \
    echo "memory_limit=256M"; \
    } > /usr/local/etc/php/conf.d/dreamland-uploads.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

COPY . .

# Gitignored locally — create production config from env-driven templates
RUN cp common/config/main-local.example.php common/config/main-local.php \
    && cp common/config/params-supabase.example.php common/config/params-local.php \
    && cp api/config/main-local.example.php api/config/main-local.php \
    && cp api/config/params-local.example.php api/config/params-local.php

RUN mkdir -p frontend/web/uploads api/runtime/uploads/user api/runtime/uploads/image api/runtime/uploads/video \
    api/runtime backend/runtime common/runtime backend/web/assets \
    && chown -R www-data:www-data frontend/web/uploads api/runtime backend/runtime common/runtime backend/web/assets

COPY api/docker/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY api/docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]
