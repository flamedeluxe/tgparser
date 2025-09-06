<?php

require_once 'vendor/autoload.php';

use TgParser\Database;

echo "🗄️ Инициализация базы данных SQLite...\n";

try {
    // Создаем экземпляр базы данных (автоматически создаст таблицы)
    $database = new Database();
    
    echo "✅ База данных успешно инициализирована!\n";
    echo "📁 Файл базы данных: bot_data.sqlite\n";
    
    // Показываем статистику
    $stats = $database->getStats();
    echo "\n📊 Текущая статистика:\n";
    echo "📝 Сообщений: " . $stats['total_messages'] . "\n";
    echo "📺 Каналов: " . $stats['total_channels'] . "\n";
    echo "🕐 Последний парсинг: " . $stats['last_parsing'] . "\n";
    
    echo "\n🎉 Готово! Теперь можно запускать бота.\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка при инициализации базы данных: " . $e->getMessage() . "\n";
    exit(1);
}
