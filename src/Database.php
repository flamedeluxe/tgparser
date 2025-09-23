<?php

namespace TgParser;

use PDO;
use PDOException;
use Exception;
use Longman\TelegramBot\Request;

class Database
{
    private $pdo;
    private $dbPath;

    public function __construct($dbPath = 'bot_data.sqlite')
    {
        $this->dbPath = $dbPath;
        $this->connect();
        $this->createTables();
    }

    /**
     * Подключение к базе данных SQLite
     */
    private function connect()
    {
        try {
            $this->pdo = new PDO("sqlite:" . $this->dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Ошибка подключения к базе данных: " . $e->getMessage());
        }
    }

    /**
     * Создание таблиц
     */
    private function createTables()
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS channels (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            chat_id TEXT UNIQUE NOT NULL,
            chat_title TEXT,
            chat_type TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            message_id TEXT NOT NULL,
            chat_id TEXT NOT NULL,
            from_user TEXT,
            text_content TEXT,
            caption TEXT,
            media_type TEXT,
            media_file_id TEXT,
            media_file_path TEXT,
            media_url TEXT,
            message_date INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (chat_id) REFERENCES channels(chat_id)
        );

        CREATE TABLE IF NOT EXISTS parsing_stats (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            total_messages INTEGER DEFAULT 0,
            total_channels INTEGER DEFAULT 0,
            last_parsing DATETIME,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        ";

        $this->pdo->exec($sql);
    }

    /**
     * Сохранение информации о канале
     */
    public function saveChannel($chatId, $chatTitle, $chatType)
    {
        $sql = "INSERT OR REPLACE INTO channels (chat_id, chat_title, chat_type) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$chatId, $chatTitle, $chatType]);
    }

    /**
     * Сохранение сообщения
     */
    public function saveMessage($data)
    {
        $sql = "INSERT INTO messages (
            message_id, chat_id, from_user, text_content, caption, 
            media_type, media_file_id, media_file_path, media_url, message_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['message_id'],
            $data['chat_id'],
            $data['from_user'],
            $data['text_content'],
            $data['caption'],
            $data['media_type'] ?? null,
            $data['media_file_id'] ?? null,
            $data['media_file_path'] ?? null,
            $data['media_url'] ?? null,
            $data['message_date']
        ]);
    }

    /**
     * Получение всех каналов
     */
    public function getChannels()
    {
        $sql = "SELECT * FROM channels ORDER BY created_at DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Получение сообщений по каналу
     */
    public function getMessagesByChannel($chatId, $limit = 100, $offset = 0)
    {
        $sql = "SELECT m.*, c.chat_title, c.chat_type 
                FROM messages m 
                LEFT JOIN channels c ON m.chat_id = c.chat_id 
                WHERE m.chat_id = ? 
                ORDER BY m.message_date DESC 
                LIMIT ? OFFSET ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$chatId, $limit, $offset]);
        return $stmt->fetchAll();
    }

    /**
     * Получение всех сообщений
     */
    public function getAllMessages($limit = 1000)
    {
        $sql = "SELECT m.*, c.chat_title, c.chat_type 
                FROM messages m 
                LEFT JOIN channels c ON m.chat_id = c.chat_id 
                ORDER BY m.message_date DESC 
                LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Получение статистики
     */
    public function getStats()
    {
        // Общее количество сообщений
        $sql = "SELECT COUNT(*) as total_messages FROM messages";
        $stmt = $this->pdo->query($sql);
        $totalMessages = $stmt->fetch()['total_messages'];

        // Общее количество каналов
        $sql = "SELECT COUNT(*) as total_channels FROM channels";
        $stmt = $this->pdo->query($sql);
        $totalChannels = $stmt->fetch()['total_channels'];

        // Последний парсинг
        $sql = "SELECT MAX(created_at) as last_parsing FROM messages";
        $stmt = $this->pdo->query($sql);
        $lastParsing = $stmt->fetch()['last_parsing'];

        return [
            'total_messages' => $totalMessages,
            'total_channels' => $totalChannels,
            'last_parsing' => $lastParsing ?: 'Никогда'
        ];
    }

    /**
     * Поиск сообщений по тексту
     */
    public function searchMessages($query, $limit = 100)
    {
        $sql = "SELECT m.*, c.chat_title, c.chat_type 
                FROM messages m 
                LEFT JOIN channels c ON m.chat_id = c.chat_id 
                WHERE m.text_content LIKE ? OR m.caption LIKE ?
                ORDER BY m.message_date DESC 
                LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $searchTerm = "%{$query}%";
        $stmt->execute([$searchTerm, $searchTerm, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Получение сообщений по типу медиа
     */
    public function getMessagesByMediaType($mediaType, $limit = 100)
    {
        $sql = "SELECT m.*, c.chat_title, c.chat_type 
                FROM messages m 
                LEFT JOIN channels c ON m.chat_id = c.chat_id 
                WHERE m.media_type = ?
                ORDER BY m.message_date DESC 
                LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$mediaType, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Очистка старых данных (старше указанного количества дней)
     */
    public function cleanOldData($days = 30)
    {
        $sql = "DELETE FROM messages WHERE created_at < datetime('now', '-{$days} days')";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute();
    }

    /**
     * Получение PDO объекта для прямых запросов
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * Скачивание медиафайла по file_id
     */
    public static function downloadMediaFile($fileId, $mediaType, $chatId, $messageId)
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
            $fileName = "{$mediaType}_{$chatId}_{$messageId}_{$fileId}.{$extension}";
            $localPath = $mediaDir . '/' . $fileName;

            // Скачиваем файл
            $botToken = $_ENV['BOT_TOKEN'] ?? '';
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
}
