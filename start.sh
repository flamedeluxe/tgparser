#!/bin/bash

# Скрипт запуска Telegram бота

echo "🤖 Запуск Telegram Parser Bot..."

# Проверяем наличие PHP
if ! command -v php &> /dev/null; then
    echo "❌ PHP не найден. Установите PHP 7.4 или выше."
    exit 1
fi

# Проверяем наличие Composer
if ! command -v composer &> /dev/null; then
    echo "❌ Composer не найден. Установите Composer."
    exit 1
fi

# Устанавливаем зависимости если нужно
if [ ! -d "vendor" ]; then
    echo "📦 Установка зависимостей..."
    composer install
fi

# Проверяем наличие конфигурации
if [ ! -f ".env" ]; then
    if [ -f "config.env" ]; then
        echo "📋 Копирование конфигурации..."
        cp config.env .env
    else
        echo "❌ Файл конфигурации не найден."
        exit 1
    fi
fi

# Инициализируем базу данных если нужно
if [ ! -f "bot_data.sqlite" ]; then
    echo "🗄️ Инициализация базы данных..."
    php init_db.php
fi

# Запускаем бота
echo "🚀 Запуск бота..."
php bot.php
