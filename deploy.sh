#!/bin/bash

# Скрипт деплоя на продакшн сервер
# Использование: ./deploy.sh

set -e

# Конфигурация
SERVER="my"
DOMAIN="parser.dev-asgart.ru"
REMOTE_PATH="/var/www/tgparser"
SERVICE_NAME="tgparser-bot"

echo "🚀 Деплой Telegram Parser Bot на продакшн сервер..."

# Проверяем подключение к серверу
echo "📡 Проверка подключения к серверу..."
ssh $SERVER "echo '✅ Подключение к серверу успешно'"

# Создаем директории на сервере
echo "📁 Создание директорий на сервере..."
ssh $SERVER "mkdir -p $REMOTE_PATH/{public,data,logs,storage}"

# Копируем файлы проекта
echo "📦 Копирование файлов проекта..."
rsync -avz --exclude='.git' --exclude='node_modules' --exclude='vendor' --exclude='*.log' --exclude='bot_data.sqlite' ./ $SERVER:$REMOTE_PATH/

# Устанавливаем зависимости на сервере
echo "📚 Установка зависимостей на сервере..."
ssh $SERVER "cd $REMOTE_PATH && composer install --no-dev --optimize-autoloader"

# Копируем конфигурацию
echo "⚙️ Настройка конфигурации..."
ssh $SERVER "cd $REMOTE_PATH && cp production.env .env"

# Устанавливаем права доступа
echo "🔐 Настройка прав доступа..."
ssh $SERVER "chown -R www-data:www-data $REMOTE_PATH"
ssh $SERVER "chmod -R 755 $REMOTE_PATH"
ssh $SERVER "chmod 777 $REMOTE_PATH/data"
ssh $SERVER "chmod 777 $REMOTE_PATH/logs"

# Инициализируем базу данных если нужно
echo "🗄️ Инициализация базы данных..."
ssh $SERVER "cd $REMOTE_PATH && php init_db.php"

# Копируем конфигурацию Nginx
echo "🌐 Настройка Nginx..."
ssh $SERVER "sudo cp $REMOTE_PATH/nginx-production.conf /etc/nginx/sites-available/$DOMAIN"
ssh $SERVER "sudo ln -sf /etc/nginx/sites-available/$DOMAIN /etc/nginx/sites-enabled/"
ssh $SERVER "sudo nginx -t"

# Перезапускаем Nginx
echo "🔄 Перезапуск Nginx..."
ssh $SERVER "sudo systemctl reload nginx"

# Создаем systemd сервис для бота
echo "🤖 Настройка сервиса бота..."
ssh $SERVER "sudo tee /etc/systemd/system/$SERVICE_NAME.service > /dev/null <<EOF
[Unit]
Description=Telegram Parser Bot
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=$REMOTE_PATH
ExecStart=/usr/bin/php $REMOTE_PATH/bot.php
Restart=always
RestartSec=10
StandardOutput=append:$REMOTE_PATH/logs/bot.log
StandardError=append:$REMOTE_PATH/logs/bot.log

[Install]
WantedBy=multi-user.target
EOF"

# Перезагружаем systemd и запускаем сервис
echo "🔄 Запуск сервиса бота..."
ssh $SERVER "sudo systemctl daemon-reload"
ssh $SERVER "sudo systemctl enable $SERVICE_NAME"
ssh $SERVER "sudo systemctl restart $SERVICE_NAME"

# Проверяем статус сервисов
echo "📊 Проверка статуса сервисов..."
ssh $SERVER "sudo systemctl status $SERVICE_NAME --no-pager"
ssh $SERVER "sudo systemctl status nginx --no-pager"

# Проверяем API
echo "🌐 Проверка API..."
sleep 5
curl -H "X-API-Key: prod_api_key_2024_secure" "https://$DOMAIN/api/stats" || echo "⚠️ API пока недоступен"

echo ""
echo "✅ Деплой завершен!"
echo "🌐 Сайт: https://$DOMAIN"
echo "📊 API: https://$DOMAIN/api/stats"
echo "📚 Документация: https://$DOMAIN/API.md"
echo ""
echo "🔧 Полезные команды:"
echo "  Просмотр логов: ssh $SERVER 'tail -f $REMOTE_PATH/logs/bot.log'"
echo "  Статус бота: ssh $SERVER 'sudo systemctl status $SERVICE_NAME'"
echo "  Перезапуск бота: ssh $SERVER 'sudo systemctl restart $SERVICE_NAME'"
