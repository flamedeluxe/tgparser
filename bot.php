<?php

require_once 'vendor/autoload.php';

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Exception\TelegramException;
use TgParser\Database;

// Загружаем переменные окружения
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Инициализируем базу данных
$database = new Database();

// Конфигурация бота
$bot_token = $_ENV['BOT_TOKEN'] ?? '7871178627:AAFY2IYhkaf0GdlDBRC8Tvg26SXRMMVvwi0';
$bot_username = $_ENV['BOT_USERNAME'] ?? 'tgparser2_bot';

try {
    // Создаем экземпляр бота
    $telegram = new Telegram($bot_token, $bot_username);
    
    // Получаем обновления
    $updates = $telegram->handleGetUpdates();
    
    if (empty($updates) || !is_array($updates)) {
        echo "Нет новых сообщений.\n";
        exit;
    }
    
    foreach ($updates as $update) {
        if ($update instanceof Update) {
            handleUpdate($update, $telegram);
        }
    }
    
} catch (TelegramException $e) {
    echo "Ошибка Telegram: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Общая ошибка: " . $e->getMessage() . "\n";
}

/**
 * Скачивание медиафайла по file_id
 */
function downloadMediaFile($telegram, $fileId, $mediaType, $chatId, $messageId)
{
    try {
        // Создаем папку для медиафайлов, если ее нет
        $mediaDir = __DIR__ . '/media';
        if (!is_dir($mediaDir)) {
            mkdir($mediaDir, 0755, true);
        }

        // Получаем информацию о файле
        $file = Request::getFile(['file_id' => $fileId]);
        
        if (!$file->isOk()) {
            error_log("Failed to get file info for file_id: {$fileId}");
            return null;
        }

        $fileData = $file->getResult();
        $filePath = $fileData->getFilePath();
        
        if (!$filePath) {
            error_log("No file path received for file_id: {$fileId}");
            return null;
        }

        // Определяем расширение файла
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        if (empty($extension)) {
            // Для разных типов медиа задаем расширение по умолчанию
            switch ($mediaType) {
                case 'photo':
                    $extension = 'jpg';
                    break;
                case 'video':
                    $extension = 'mp4';
                    break;
                case 'audio':
                    $extension = 'mp3';
                    break;
                case 'voice':
                    $extension = 'ogg';
                    break;
                case 'sticker':
                    $extension = 'webp';
                    break;
                default:
                    $extension = 'bin';
            }
        }

        // Генерируем имя файла
        $fileName = "{$mediaType}_{$chatId}_{$messageId}_" . substr($fileId, -10) . ".{$extension}";
        $localPath = $mediaDir . '/' . $fileName;

        // Скачиваем файл
        $botToken = $_ENV['BOT_TOKEN'] ?? '7871178627:AAFY2IYhkaf0GdlDBRC8Tvg26SXRMMVvwi0';
        $downloadUrl = "https://api.telegram.org/file/bot{$botToken}/{$filePath}";
        
        $fileContent = file_get_contents($downloadUrl);
        if ($fileContent === false) {
            error_log("Failed to download file from: {$downloadUrl}");
            return null;
        }

        // Сохраняем файл
        if (file_put_contents($localPath, $fileContent) === false) {
            error_log("Failed to save file to: {$localPath}");
            return null;
        }

        // Возвращаем относительный путь
        return 'media/' . $fileName;

    } catch (Exception $e) {
        error_log("Error downloading media file: " . $e->getMessage());
        return null;
    }
}

/**
 * Обработка входящих обновлений
 */
function handleUpdate(Update $update, Telegram $telegram) {
    $message = $update->getMessage();
    
    if (!$message) {
        return;
    }
    
    $chat_id = $message->getChat()->getId();
    $text = $message->getText();
    $chat_type = $message->getChat()->getType();
    
    // Логируем информацию о сообщении
    echo "Получено сообщение в чате {$chat_id} (тип: {$chat_type}): {$text}\n";
    
    // Если это канал или группа, парсим контент
    if (in_array($chat_type, ['channel', 'group', 'supergroup'])) {
        parseChannelContent($message, $telegram, $database);
    }
    
    // Обработка команд
    if ($text && strpos($text, '/') === 0) {
        handleCommand($text, $chat_id, $telegram, $database);
    }
}

/**
 * Парсинг контента канала
 */
function parseChannelContent($message, Telegram $telegram, Database $database) {
    $chat_id = $message->getChat()->getId();
    $message_id = $message->getMessageId();
    $text = $message->getText();
    $caption = $message->getCaption();
    
    // Сохраняем информацию о канале
    $chat_title = $message->getChat()->getTitle();
    $chat_type = $message->getChat()->getType();
    $database->saveChannel($chat_id, $chat_title, $chat_type);
    
    // Собираем информацию о сообщении
    $content = [
        'message_id' => $message_id,
        'chat_id' => $chat_id,
        'from_user' => $message->getFrom() ? $message->getFrom()->getUsername() : 'Unknown',
        'text_content' => $text,
        'caption' => $caption,
        'message_date' => $message->getDate(),
    ];
    
    // Проверяем наличие медиа и скачиваем файлы
    if ($message->getPhoto()) {
        $content['media_type'] = 'photo';
        $photos = $message->getPhoto();
        $fileId = end($photos)->getFileId();
        $content['media_file_id'] = $fileId;
        
        // Скачиваем файл
        $filePath = downloadMediaFile($telegram, $fileId, 'photo', $chat_id, $message_id);
        if ($filePath) {
            $content['media_file_path'] = $filePath;
        }
    } elseif ($message->getVideo()) {
        $content['media_type'] = 'video';
        $fileId = $message->getVideo()->getFileId();
        $content['media_file_id'] = $fileId;
        
        // Скачиваем файл
        $filePath = downloadMediaFile($telegram, $fileId, 'video', $chat_id, $message_id);
        if ($filePath) {
            $content['media_file_path'] = $filePath;
        }
    } elseif ($message->getDocument()) {
        $content['media_type'] = 'document';
        $fileId = $message->getDocument()->getFileId();
        $content['media_file_id'] = $fileId;
        
        // Скачиваем файл
        $filePath = downloadMediaFile($telegram, $fileId, 'document', $chat_id, $message_id);
        if ($filePath) {
            $content['media_file_path'] = $filePath;
        }
    } elseif ($message->getAudio()) {
        $content['media_type'] = 'audio';
        $fileId = $message->getAudio()->getFileId();
        $content['media_file_id'] = $fileId;
        
        // Скачиваем файл
        $filePath = downloadMediaFile($telegram, $fileId, 'audio', $chat_id, $message_id);
        if ($filePath) {
            $content['media_file_path'] = $filePath;
        }
    } elseif ($message->getVoice()) {
        $content['media_type'] = 'voice';
        $fileId = $message->getVoice()->getFileId();
        $content['media_file_id'] = $fileId;
        
        // Скачиваем файл
        $filePath = downloadMediaFile($telegram, $fileId, 'voice', $chat_id, $message_id);
        if ($filePath) {
            $content['media_file_path'] = $filePath;
        }
    } elseif ($message->getSticker()) {
        $content['media_type'] = 'sticker';
        $fileId = $message->getSticker()->getFileId();
        $content['media_file_id'] = $fileId;
        
        // Скачиваем файл
        $filePath = downloadMediaFile($telegram, $fileId, 'sticker', $chat_id, $message_id);
        if ($filePath) {
            $content['media_file_path'] = $filePath;
        }
    }
    
    // Сохраняем сообщение в базу данных
    $database->saveMessage($content);
    
    // Отправляем подтверждение (только в личные сообщения)
    if ($message->getChat()->getType() === 'private') {
        $response_text = "✅ Контент успешно спарсен!\n\n";
        $response_text .= "📝 Текст: " . ($text ?: $caption ?: 'Нет текста') . "\n";
        $response_text .= "📅 Дата: " . date('Y-m-d H:i:s', $content['message_date']) . "\n";
        $response_text .= "📺 Канал: " . $chat_title . "\n";
        
        if (isset($content['media_type'])) {
            $response_text .= "🎬 Медиа: " . $content['media_type'] . "\n";
        }
        
        Request::sendMessage([
            'chat_id' => $message->getFrom()->getId(),
            'text' => $response_text,
        ]);
    }
}

/**
 * Обработка команд
 */
function handleCommand($text, $chat_id, Telegram $telegram, Database $database) {
    $command = explode(' ', $text)[0];
    
    switch ($command) {
        case '/start':
            $response = "🤖 Привет! Я бот для парсинга контента из телеграм каналов.\n\n";
            $response .= "📋 Доступные команды:\n";
            $response .= "/help - Показать справку\n";
            $response .= "/status - Статус бота\n";
            $response .= "/stats - Статистика парсинга\n\n";
            $response .= "Добавьте меня в канал как администратора, и я буду парсить весь контент!";
            break;
            
        case '/help':
            $response = "📖 Справка по боту:\n\n";
            $response .= "🔧 Как использовать:\n";
            $response .= "1. Добавьте бота в канал как администратора\n";
            $response .= "2. Бот автоматически начнет парсить весь контент\n";
            $response .= "3. Парсинг происходит в реальном времени\n\n";
            $response .= "📊 Что парсится:\n";
            $response .= "• Текстовые сообщения\n";
            $response .= "• Фотографии и видео\n";
            $response .= "• Документы и аудио\n";
            $response .= "• Стикеры и голосовые сообщения\n";
            break;
            
        case '/status':
            $response = "🟢 Бот работает нормально!\n";
            $response .= "⏰ Время: " . date('Y-m-d H:i:s') . "\n";
            $response .= "🆔 ID чата: " . $chat_id . "\n";
            break;
            
        case '/stats':
            $stats = $database->getStats();
            $response = "📊 Статистика парсинга:\n\n";
            $response .= "📝 Всего сообщений: " . $stats['total_messages'] . "\n";
            $response .= "📺 Каналов: " . $stats['total_channels'] . "\n";
            $response .= "🕐 Последний парсинг: " . $stats['last_parsing'] . "\n";
            break;
            
        case '/channels':
            $channels = $database->getChannels();
            $response = "📺 Список каналов:\n\n";
            foreach ($channels as $channel) {
                $response .= "• " . $channel['chat_title'] . " (ID: " . $channel['chat_id'] . ")\n";
            }
            break;
            
        case '/search':
            $query = trim(str_replace('/search', '', $text));
            if (empty($query)) {
                $response = "❓ Использование: /search <запрос>\nПример: /search привет";
            } else {
                $messages = $database->searchMessages($query, 10);
                $response = "🔍 Результаты поиска по запросу '{$query}':\n\n";
                foreach ($messages as $msg) {
                    $response .= "📝 " . substr($msg['text_content'] ?: $msg['caption'] ?: 'Медиа', 0, 100) . "...\n";
                    $response .= "📺 " . $msg['chat_title'] . "\n\n";
                }
            }
            break;
            
        default:
            $response = "❓ Неизвестная команда. Используйте /help для справки.";
    }
    
    Request::sendMessage([
        'chat_id' => $chat_id,
        'text' => $response,
    ]);
}


// Если скрипт запущен напрямую
if (php_sapi_name() === 'cli') {
    echo "🤖 Запуск Telegram бота...\n";
    echo "🆔 Бот: @{$bot_username}\n";
    echo "🔑 Токен: " . substr($bot_token, 0, 10) . "...\n\n";
}
