<?php

namespace TgParser;

use TgParser\Database;

class ApiController
{
    private $database;
    private $apiKey;

    public function __construct(Database $database, $apiKey = null)
    {
        $this->database = $database;
        $this->apiKey = $apiKey ?: $_ENV['API_KEY'] ?? 'default_api_key';
    }

    /**
     * Обработка API запросов
     */
    public function handleRequest()
    {
        // Устанавливаем заголовки CORS
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
        header('Content-Type: application/json; charset=utf-8');

        // Обработка preflight запросов
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        // Проверка API ключа
        if (!$this->validateApiKey()) {
            $this->sendError('Неверный API ключ', 401);
            return;
        }

        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = str_replace('/api', '', $path);

        try {
            switch ($path) {
                case '/messages':
                    $this->handleMessages($method);
                    break;
                case '/channels':
                    $this->handleChannels($method);
                    break;
                case '/stats':
                    $this->handleStats($method);
                    break;
                case '/search':
                    $this->handleSearch($method);
                    break;
                default:
                    $this->sendError('Эндпоинт не найден', 404);
            }
        } catch (Exception $e) {
            $this->sendError('Внутренняя ошибка сервера: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Валидация API ключа
     */
    private function validateApiKey()
    {
        $headers = getallheaders();
        $apiKey = $headers['X-API-Key'] ?? $headers['x-api-key'] ?? null;
        
        if (!$apiKey) {
            $apiKey = $_GET['api_key'] ?? null;
        }

        return $apiKey === $this->apiKey;
    }

    /**
     * Обработка запросов к сообщениям
     */
    private function handleMessages($method)
    {
        switch ($method) {
            case 'GET':
                $this->getMessages();
                break;
            default:
                $this->sendError('Метод не поддерживается', 405);
        }
    }

    /**
     * Получение сообщений
     */
    private function getMessages()
    {
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);
        $channelId = $_GET['channel_id'] ?? null;
        $mediaType = $_GET['media_type'] ?? null;

        if ($channelId) {
            $messages = $this->database->getMessagesByChannel($channelId, $limit);
        } elseif ($mediaType) {
            $messages = $this->database->getMessagesByMediaType($mediaType, $limit);
        } else {
            $messages = $this->database->getAllMessages($limit);
        }

        // Применяем offset
        $messages = array_slice($messages, $offset);

        $this->sendSuccess([
            'messages' => $messages,
            'total' => count($messages),
            'limit' => $limit,
            'offset' => $offset
        ]);
    }

    /**
     * Обработка запросов к каналам
     */
    private function handleChannels($method)
    {
        switch ($method) {
            case 'GET':
                $this->getChannels();
                break;
            default:
                $this->sendError('Метод не поддерживается', 405);
        }
    }

    /**
     * Получение каналов
     */
    private function getChannels()
    {
        $channels = $this->database->getChannels();
        $this->sendSuccess(['channels' => $channels]);
    }

    /**
     * Обработка запросов к статистике
     */
    private function handleStats($method)
    {
        switch ($method) {
            case 'GET':
                $this->getStats();
                break;
            default:
                $this->sendError('Метод не поддерживается', 405);
        }
    }

    /**
     * Получение статистики
     */
    private function getStats()
    {
        $stats = $this->database->getStats();
        $this->sendSuccess(['stats' => $stats]);
    }

    /**
     * Обработка поиска
     */
    private function handleSearch($method)
    {
        switch ($method) {
            case 'GET':
                $this->searchMessages();
                break;
            default:
                $this->sendError('Метод не поддерживается', 405);
        }
    }

    /**
     * Поиск сообщений
     */
    private function searchMessages()
    {
        $query = $_GET['q'] ?? '';
        $limit = (int)($_GET['limit'] ?? 20);

        if (empty($query)) {
            $this->sendError('Параметр q (запрос) обязателен', 400);
            return;
        }

        $messages = $this->database->searchMessages($query, $limit);
        $this->sendSuccess([
            'messages' => $messages,
            'query' => $query,
            'total' => count($messages)
        ]);
    }

    /**
     * Отправка успешного ответа
     */
    private function sendSuccess($data, $code = 200)
    {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'timestamp' => date('c')
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Отправка ошибки
     */
    private function sendError($message, $code = 400)
    {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message,
            'timestamp' => date('c')
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
