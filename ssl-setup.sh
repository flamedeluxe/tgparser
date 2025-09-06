#!/bin/bash

# Скрипт настройки SSL для parser.dev-asgart.ru
# Запускать на сервере

set -e

DOMAIN="parser.dev-asgart.ru"
EMAIL="admin@dev-asgart.ru"

echo "🔒 Настройка SSL для $DOMAIN..."

# Устанавливаем Certbot если не установлен
if ! command -v certbot &> /dev/null; then
    echo "📦 Установка Certbot..."
    sudo apt update
    sudo apt install -y certbot python3-certbot-nginx
fi

# Останавливаем Nginx временно
echo "⏸️ Остановка Nginx..."
sudo systemctl stop nginx

# Получаем SSL сертификат
echo "🔐 Получение SSL сертификата..."
sudo certbot certonly --standalone -d $DOMAIN --email $EMAIL --agree-tos --non-interactive

# Проверяем сертификат
echo "✅ Проверка сертификата..."
sudo certbot certificates

# Настраиваем автообновление
echo "🔄 Настройка автообновления сертификата..."
sudo crontab -l | grep -v certbot | sudo crontab -
echo "0 12 * * * /usr/bin/certbot renew --quiet" | sudo crontab -

# Запускаем Nginx
echo "🚀 Запуск Nginx..."
sudo systemctl start nginx

echo "✅ SSL настроен для $DOMAIN"
echo "🌐 Проверьте сайт: https://$DOMAIN"
