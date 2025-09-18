<?php

/**
 * Тестовый скрипт для проверки работы системы скачивания медиафайлов
 */

require_once 'vendor/autoload.php';

use TgParser\Database;

echo "🧪 Тестирование системы медиафайлов...\n\n";

try {
    // Подключаемся к базе данных
    $database = new Database();
    
    // Проверяем структуру таблицы
    echo "📋 Проверка структуры таблицы messages:\n";
    $pdo = $database->getPdo();
    $stmt = $pdo->query("PRAGMA table_info(messages)");
    $columns = $stmt->fetchAll();
    
    $hasMediaFilePath = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'media_file_path') {
            $hasMediaFilePath = true;
            echo "✅ Колонка media_file_path найдена\n";
            break;
        }
    }
    
    if (!$hasMediaFilePath) {
        echo "❌ Колонка media_file_path не найдена!\n";
        exit(1);
    }
    
    // Проверяем папку media
    $mediaDir = __DIR__ . '/media';
    if (is_dir($mediaDir)) {
        echo "✅ Папка media существует: {$mediaDir}\n";
        echo "📁 Права доступа: " . substr(sprintf('%o', fileperms($mediaDir)), -4) . "\n";
    } else {
        echo "❌ Папка media не существует!\n";
    }
    
    // Проверяем последние сообщения с медиа
    echo "\n📊 Проверка последних медиафайлов:\n";
    $sql = "SELECT * FROM messages WHERE media_type IS NOT NULL ORDER BY created_at DESC LIMIT 5";
    $stmt = $pdo->query($sql);
    $mediaMessages = $stmt->fetchAll();
    
    if (empty($mediaMessages)) {
        echo "ℹ️  Медиафайлы в базе данных не найдены\n";
    } else {
        foreach ($mediaMessages as $message) {
            echo "📄 ID: {$message['id']}, Тип: {$message['media_type']}\n";
            echo "   File ID: {$message['media_file_id']}\n";
            
            if (!empty($message['media_file_path'])) {
                echo "   ✅ Путь: {$message['media_file_path']}\n";
                
                // Проверяем, существует ли файл
                $fullPath = __DIR__ . '/' . $message['media_file_path'];
                if (file_exists($fullPath)) {
                    echo "   ✅ Файл существует (" . formatBytes(filesize($fullPath)) . ")\n";
                } else {
                    echo "   ❌ Файл не найден: {$fullPath}\n";
                }
            } else {
                echo "   ⚠️  Путь к файлу не указан (старая запись)\n";
            }
            echo "\n";
        }
    }
    
    // Проверяем статистику
    echo "📈 Общая статистика:\n";
    $stats = $database->getStats();
    echo "   Всего сообщений: {$stats['total_messages']}\n";
    echo "   Каналов: {$stats['total_channels']}\n";
    echo "   Последний парсинг: {$stats['last_parsing']}\n";
    
    // Проверяем количество медиафайлов
    $sql = "SELECT COUNT(*) as count FROM messages WHERE media_type IS NOT NULL";
    $stmt = $pdo->query($sql);
    $mediaCount = $stmt->fetch()['count'];
    echo "   Медиафайлов в БД: {$mediaCount}\n";
    
    // Проверяем количество файлов с путями
    $sql = "SELECT COUNT(*) as count FROM messages WHERE media_file_path IS NOT NULL";
    $stmt = $pdo->query($sql);
    $pathCount = $stmt->fetch()['count'];
    echo "   С указанными путями: {$pathCount}\n";
    
    echo "\n🎉 Тестирование завершено успешно!\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка тестирования: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Форматирование размера файла
 */
function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}