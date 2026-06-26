FROM php:8.3-fpm
WORKDIR /var/www

# تغییر رپو به میرور لیارا
RUN sed -i 's|deb.debian.org|linux-mirror.liara.ir/repository|g' /etc/apt/sources.list.d/debian.sources

# نصب تمام پکیج‌های مورد نیاز
RUN apt-get -o Acquire::Check-Valid-Until=false update && \
    apt-get install -y \
    openssl zip unzip git curl \
    libzip-dev libonig-dev libicu-dev \
    autoconf pkg-config \
    libexif-dev libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    libxml2-dev \
    supervisor procps

# نصب PHP extensions اصلی
RUN docker-php-ext-configure pcntl --enable-pcntl && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-configure soap --enable-soap

RUN docker-php-ext-install pcntl exif gd zip bcmath mbstring intl opcache pdo pdo_mysql mysqli medikals soap

# نصب Redis extension
RUN mkdir -p /usr/src/php/ext/redis && \
    curl -L https://github.com/phpredis/phpredis/archive/6.1.0.tar.gz | tar xvz -C /usr/src/php/ext/redis --strip 1 && \
    docker-php-ext-install redis

# نصب کامپوزر
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# کپی composer.json و composer.lock
COPY ./src/composer.json /var/www/
COPY ./src/composer.lock /var/www/

# تنظیم میرور کامپوزر لیارا
RUN composer config -g repos.packagist composer https://package-mirror.liara.ir/repository/composer/

# نصب وابستگی‌ها (از روی lock file)
RUN cd /var/www && \
    composer install --ignore-platform-reqs --no-interaction --no-scripts --prefer-dist

# کپی بقیه فایل‌های پروژه
COPY ./src /var/www

# دامپ autoload و دیسکاور پکیج‌ها
RUN cd /var/www && \
    composer dump-autoload --optimize && \
    php artisan package:discover --ansi || true

# کپی تنظیمات Supervisor و PHP
COPY ./supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY ./php/local.ini /usr/local/etc/php/conf.d/local.ini

# ایجاد دایرکتوری‌های مورد نیاز و تنظیم دسترسی‌ها
RUN mkdir -p /var/log/supervisor && \
    chmod -R 777 storage/ && \
    chmod -R 777 bootstrap/cache/ && \
    chown -R www-data:www-data /var/www/storage && \
    chown -R www-data:www-data /var/www/bootstrap/cache

EXPOSE 9000 8082

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]