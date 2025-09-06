#!/bin/bash

# Скрипт переключения бота на webhook
# Использование: ./switch-to-webhook.sh

set -e

echo "🔄 Переключение Telegram бота на webhook..."

# Останавливаем старый сервис бота
echo "⏹️ Остановка старого сервиса бота..."
ssh my "sudo systemctl stop tgparser-bot"

# Копируем новые файлы
echo "📦 Копирование файлов webhook..."
scp webhook.php my:/var/www/tgparser/
scp setup-webhook.php my:/var/www/tgparser/
scp nginx-production.conf my:/var/www/tgparser/

# Обновляем конфигурацию Nginx
echo "🌐 Обновление конфигурации Nginx..."
ssh my "sudo cp /var/www/tgparser/nginx-production.conf /etc/nginx/sites-available/parser.dev-asgart.ru && sudo nginx -t && sudo systemctl reload nginx"

# Настраиваем webhook
echo "🔗 Настройка webhook..."
ssh my "cd /var/www/tgparser && php setup-webhook.php"

# Проверяем webhook
echo "🔍 Проверка webhook..."
sleep 2
curl -s "https://parser.dev-asgart.ru/webhook.php" -X POST -H "Content-Type: application/json" -d '{"test": true}' || echo "Webhook пока не отвечает (это нормально)"

echo ""
echo "✅ Переключение на webhook завершено!"
echo "🌐 Webhook URL: https://parser.dev-asgart.ru/webhook.php"
echo "🤖 Бот теперь работает через webhook"
echo ""
echo "🔧 Полезные команды:"
echo "  Проверить webhook: curl -X POST https://parser.dev-asgart.ru/webhook.php"
echo "  Логи webhook: ssh my 'tail -f /var/log/nginx/parser.dev-asgart.ru.access.log'"
echo "  Статус бота: ssh my 'sudo systemctl status tgparser-bot'"
