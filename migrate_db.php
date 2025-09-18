<?php

/**
 * Скрипт миграции базы данных
 * Добавляет новую колонку media_file_path в таблицу messages
 */

require_once 'vendor/autoload.php';

use TgParser\Database;

echo "🔄 Запуск миграции базы данных...\n";

try {
    // Подключаемся к базе данных
    $database = new Database();
    $pdo = $database->getPdo();
    
    // Проверяем, существует ли уже колонка media_file_path
    $stmt = $pdo->query("PRAGMA table_info(messages)");
    $columns = $stmt->fetchAll();
    
    $columnExists = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'media_file_path') {
            $columnExists = true;
            break;
        }
    }
    
    if ($columnExists) {
        echo "✅ Колонка media_file_path уже существует. Миграция не требуется.\n";
    } else {
        echo "➕ Добавляем колонку media_file_path...\n";
        
        // Добавляем новую колонку
        $sql = "ALTER TABLE messages ADD COLUMN media_file_path TEXT";
        $pdo->exec($sql);
        
        echo "✅ Колонка media_file_path успешно добавлена!\n";
    }
    
    // Создаем папку для медиафайлов, если ее нет
    $mediaDir = __DIR__ . '/media';
    if (!is_dir($mediaDir)) {
        mkdir($mediaDir, 0755, true);
        echo "📁 Создана папка для медиафайлов: {$mediaDir}\n";
    } else {
        echo "📁 Папка для медиафайлов уже существует: {$mediaDir}\n";
    }
    
    echo "🎉 Миграция завершена успешно!\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка миграции: " . $e->getMessage() . "\n";
    exit(1);
}