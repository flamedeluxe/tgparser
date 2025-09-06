FROM php:8.1-fpm-alpine

# Устанавливаем системные зависимости
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite \
    nginx

# Устанавливаем PHP расширения
RUN docker-php-ext-install pdo pdo_sqlite

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Создаем рабочую директорию
WORKDIR /var/www/html

# Копируем файлы проекта
COPY . .

# Устанавливаем зависимости
RUN composer install --no-dev --optimize-autoloader

# Создаем директории для логов и данных
RUN mkdir -p /var/log/nginx /var/www/html/storage

# Устанавливаем права доступа
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Копируем конфигурацию PHP-FPM
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Создаем скрипт запуска
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Открываем порт
EXPOSE 9000

# Запускаем приложение
CMD ["/usr/local/bin/start.sh"]
