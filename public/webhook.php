<?php

require_once '../vendor/autoload.php';

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Exception\TelegramException;
use TgParser\Database;

// Загружаем переменные окружения
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Инициализируем базу данных
$database = new Database();

// Конфигурация бота
$bot_token = $_ENV['BOT_TOKEN'] ?? '7871178627:AAFY2IYhkaf0GdlDBRC8Tvg26SXRMMVvwi0';
$bot_username = $_ENV['BOT_USERNAME'] ?? 'tgparser2_bot';

try {
    // Создаем экземпляр бота
    $telegram = new Telegram($bot_token, $bot_username);
    
    // Получаем входящее обновление
    $input = file_get_contents('php://input');
    $update_data = json_decode($input, true);
    
    if (!$update_data) {
        http_response_code(400);
        echo "Invalid JSON data";
        exit;
    }
    
    // Создаем объект Update
    $update = new Update($update_data, $bot_username);
    
    // Обрабатываем обновление
    handleUpdate($update, $telegram, $database);
    
    // Отвечаем OK
    http_response_code(200);
    echo "OK";
    
} catch (TelegramException $e) {
    error_log("Telegram Error: " . $e->getMessage());
    http_response_code(500);
    echo "Telegram Error";
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    http_response_code(500);
    echo "General Error";
}

/**
 * Скачивание медиафайла по file_id
 */
function downloadMediaFile($telegram, $fileId, $mediaType, $chatId, $messageId)
{
    try {
        // Создаем папку для медиафайлов, если ее нет
        $mediaDir = __DIR__ . '/../media';
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
        return '../media/' . $fileName;

    } catch (Exception $e) {
        error_log("Error downloading media file: " . $e->getMessage());
        return null;
    }
}

/**
 * Обработка входящих обновлений
 */
function handleUpdate(Update $update, Telegram $telegram, Database $database) {
    $message = $update->getMessage();
    
    // Обрабатываем обычные сообщения
    if ($message) {
        $chat_id = $message->getChat()->getId();
        $text = $message->getText();
        $chat_type = $message->getChat()->getType();
        
        // Логируем информацию о сообщении
        error_log("Получено сообщение в чате {$chat_id} (тип: {$chat_type}): {$text}");
        
        // Если это канал или группа, парсим контент
        if (in_array($chat_type, ['channel', 'group', 'supergroup'])) {
            parseChannelContent($message, $telegram, $database);
        }
        
        // Обработка команд
        if ($text && strpos($text, '/') === 0) {
            handleCommand($text, $chat_id, $telegram, $database);
        }
    }
    
    // Обрабатываем сообщения из каналов (channel_post)
    $update_data = $update->getRawData();
    if (isset($update_data['channel_post'])) {
        $channel_post_data = $update_data['channel_post'];
        $chat_id = $channel_post_data['chat']['id'];
        $text = $channel_post_data['text'] ?? '';
        $chat_type = $channel_post_data['chat']['type'];
        $chat_title = $channel_post_data['chat']['title'] ?? 'Unknown Channel';
        
        // Логируем информацию о сообщении из канала
        error_log("Получено сообщение из канала {$chat_id} (тип: {$chat_type}): {$text}");
        
        // Парсим контент из канала напрямую
        parseChannelContentFromData($channel_post_data, $telegram, $database);
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

/**
 * Парсинг контента канала из данных
 */
function parseChannelContentFromData($channel_post_data, Telegram $telegram, Database $database) {
    $chat_id = $channel_post_data['chat']['id'];
    $message_id = $channel_post_data['message_id'];
    $text = $channel_post_data['text'] ?? '';
    $caption = $channel_post_data['caption'] ?? '';
    
    // Сохраняем информацию о канале
    $chat_title = $channel_post_data['chat']['title'] ?? 'Unknown Channel';
    $chat_type = $channel_post_data['chat']['type'];
    $database->saveChannel($chat_id, $chat_title, $chat_type);
    
    // Собираем информацию о сообщении
    $content = [
        'message_id' => $message_id,
        'chat_id' => $chat_id,
        'from_user' => 'Channel',
        'text_content' => $text,
        'caption' => $caption,
        'message_date' => $channel_post_data['date'],
    ];
    
    // Проверяем наличие медиа и скачиваем файлы
    if (isset($channel_post_data['photo'])) {
        $content['media_type'] = 'photo';
        $photos = $channel_post_data['photo'];
        $fileId = end($photos)['file_id'];
        $content['media_file_id'] = $fileId;
        
        // Скачиваем файл
        $filePath = downloadMediaFile($telegram, $fileId, 'photo', $chat_id, $message_id);
        if ($filePath) {
            $content['media_file_path'] = $filePath;
        }
    } elseif (isset($channel_post_data['video'])) {
        $content['media_type'] = 'video';
        $fileId = $channel_post_data['video']['file_id'];
        $content['media_file_id'] = $fileId;
        
        // Скачиваем файл
        $filePath = downloadMediaFile($telegram, $fileId, 'video', $chat_id, $message_id);
        if ($filePath) {
            $content['media_file_path'] = $filePath;
        }
    } elseif (isset($channel_post_data['document'])) {
        $content['media_type'] = 'document';
        $fileId = $channel_post_data['document']['file_id'];
        $content['media_file_id'] = $fileId;
        
        // Скачиваем файл
        $filePath = downloadMediaFile($telegram, $fileId, 'document', $chat_id, $message_id);
        if ($filePath) {
            $content['media_file_path'] = $filePath;
        }
    } elseif (isset($channel_post_data['audio'])) {
        $content['media_type'] = 'audio';
        $fileId = $channel_post_data['audio']['file_id'];
        $content['media_file_id'] = $fileId;
        
        // Скачиваем файл
        $filePath = downloadMediaFile($telegram, $fileId, 'audio', $chat_id, $message_id);
        if ($filePath) {
            $content['media_file_path'] = $filePath;
        }
    } elseif (isset($channel_post_data['voice'])) {
        $content['media_type'] = 'voice';
        $fileId = $channel_post_data['voice']['file_id'];
        $content['media_file_id'] = $fileId;
        
        // Скачиваем файл
        $filePath = downloadMediaFile($telegram, $fileId, 'voice', $chat_id, $message_id);
        if ($filePath) {
            $content['media_file_path'] = $filePath;
        }
    } elseif (isset($channel_post_data['sticker'])) {
        $content['media_type'] = 'sticker';
        $fileId = $channel_post_data['sticker']['file_id'];
        $content['media_file_id'] = $fileId;
        
        // Скачиваем файл
        $filePath = downloadMediaFile($telegram, $fileId, 'sticker', $chat_id, $message_id);
        if ($filePath) {
            $content['media_file_path'] = $filePath;
        }
    }
    
    // Сохраняем сообщение в базу данных
    $database->saveMessage($content);
    
    error_log("Сообщение из канала сохранено: {$text}");
}
