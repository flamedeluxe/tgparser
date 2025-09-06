#!/bin/bash

echo "🐳 Сборка Docker образа для Telegram Parser Bot..."

# Сборка образа
docker build -t tgparser-bot .

if [ $? -eq 0 ]; then
    echo "✅ Образ успешно собран!"
    echo "📦 Имя образа: tgparser-bot"
    echo ""
    echo "🚀 Для запуска используйте:"
    echo "   docker-compose up -d"
    echo ""
    echo "🔍 Для просмотра образов:"
    echo "   docker images | grep tgparser"
else
    echo "❌ Ошибка при сборке образа"
    exit 1
fi
