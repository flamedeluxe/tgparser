#!/bin/sh

echo "🚀 Запуск Telegram Parser Bot в Docker..."

# Копируем конфигурацию Nginx
cp /var/www/html/docker/nginx.conf /etc/nginx/conf.d/default.conf
cp /var/www/html/docker/nginx-main.conf /etc/nginx/nginx.conf

# Инициализируем базу данных если нужно
if [ ! -f "/var/www/html/bot_data.sqlite" ]; then
    echo "🗄️ Инициализация базы данных..."
    php /var/www/html/init_db.php
fi

# Создаем директории для логов
mkdir -p /var/log/nginx /var/log/php-fpm

# Запускаем PHP-FPM
echo "🐘 Запуск PHP-FPM..."
php-fpm -D

# Ждем немного для инициализации
sleep 2

# Запускаем Nginx в фоне
echo "🌐 Запуск Nginx..."
nginx -g "daemon off;" &

# Ждем запуска Nginx
sleep 2

echo "✅ Сервисы запущены!"
echo "🌐 API доступен по адресу: http://localhost:80/api"
echo "📚 Документация: http://localhost:80/API.md"
echo "🏠 Главная страница: http://localhost:80"

# Запускаем бота в фоне (опционально)
if [ "$RUN_BOT" = "true" ]; then
    echo "🤖 Запуск Telegram бота..."
    php /var/www/html/bot.php &
fi

# Ожидаем завершения
wait
