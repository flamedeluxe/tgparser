# Telegram Parser Bot API

REST API для доступа к данным, собранным телеграм ботом.

## Базовый URL

```
http://your-domain.com/api
```

## Аутентификация

Все запросы требуют API ключ, который передается через:

- **Заголовок**: `X-API-Key: your_api_key`
- **Параметр**: `?api_key=your_api_key`

## Эндпоинты

### 1. Получение сообщений

**GET** `/api/messages`

Получить список сообщений с возможностью фильтрации.

#### Параметры запроса:

| Параметр | Тип | Описание | По умолчанию |
|----------|-----|----------|--------------|
| `limit` | integer | Количество сообщений | 50 |
| `offset` | integer | Смещение для пагинации | 0 |
| `channel_id` | string | ID канала для фильтрации | - |
| `media_type` | string | Тип медиа (photo, video, document, audio, voice, sticker) | - |

#### Примеры запросов:

```bash
# Получить последние 20 сообщений
GET /api/messages?limit=20&api_key=your_api_key

# Получить сообщения из конкретного канала
GET /api/messages?channel_id=-1001234567890&api_key=your_api_key

# Получить только фото
GET /api/messages?media_type=photo&api_key=your_api_key

# Пагинация
GET /api/messages?limit=10&offset=20&api_key=your_api_key
```

#### Ответ:

```json
{
  "success": true,
  "data": {
    "messages": [
      {
        "id": 1,
        "message_id": "123",
        "chat_id": "-1001234567890",
        "from_user": "username",
        "text_content": "Текст сообщения",
        "caption": "Подпись к медиа",
        "media_type": "photo",
        "media_file_id": "BAADBAADrwADBREAAYag",
        "message_date": 1640995200,
        "created_at": "2023-01-01 12:00:00",
        "chat_title": "Название канала",
        "chat_type": "channel"
      }
    ],
    "total": 1,
    "limit": 50,
    "offset": 0
  },
  "timestamp": "2023-01-01T12:00:00+00:00"
}
```

### 2. Получение каналов

**GET** `/api/channels`

Получить список всех каналов/групп.

#### Пример запроса:

```bash
GET /api/channels?api_key=your_api_key
```

#### Ответ:

```json
{
  "success": true,
  "data": {
    "channels": [
      {
        "id": 1,
        "chat_id": "-1001234567890",
        "chat_title": "Название канала",
        "chat_type": "channel",
        "created_at": "2023-01-01 12:00:00"
      }
    ]
  },
  "timestamp": "2023-01-01T12:00:00+00:00"
}
```

### 3. Статистика

**GET** `/api/stats`

Получить статистику парсинга.

#### Пример запроса:

```bash
GET /api/stats?api_key=your_api_key
```

#### Ответ:

```json
{
  "success": true,
  "data": {
    "stats": {
      "total_messages": 1500,
      "total_channels": 5,
      "last_parsing": "2023-01-01 12:00:00"
    }
  },
  "timestamp": "2023-01-01T12:00:00+00:00"
}
```

### 4. Поиск сообщений

**GET** `/api/search`

Поиск по тексту сообщений.

#### Параметры запроса:

| Параметр | Тип | Описание | Обязательный |
|----------|-----|----------|--------------|
| `q` | string | Поисковый запрос | Да |
| `limit` | integer | Количество результатов | Нет (20) |

#### Пример запроса:

```bash
GET /api/search?q=привет&limit=10&api_key=your_api_key
```

#### Ответ:

```json
{
  "success": true,
  "data": {
    "messages": [
      {
        "id": 1,
        "message_id": "123",
        "chat_id": "-1001234567890",
        "text_content": "Привет всем!",
        "chat_title": "Название канала",
        "message_date": 1640995200
      }
    ],
    "query": "привет",
    "total": 1
  },
  "timestamp": "2023-01-01T12:00:00+00:00"
}
```

## Коды ответов

| Код | Описание |
|-----|----------|
| 200 | Успешный запрос |
| 400 | Неверный запрос |
| 401 | Неверный API ключ |
| 404 | Эндпоинт не найден |
| 405 | Метод не поддерживается |
| 500 | Внутренняя ошибка сервера |

## Обработка ошибок

При ошибке API возвращает JSON с описанием проблемы:

```json
{
  "success": false,
  "error": "Описание ошибки",
  "timestamp": "2023-01-01T12:00:00+00:00"
}
```

## CORS

API поддерживает CORS для запросов из браузера:

- `Access-Control-Allow-Origin: *`
- `Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS`
- `Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key`

## Примеры использования

### JavaScript (fetch)

```javascript
const apiKey = 'your_api_key';
const baseUrl = 'http://your-domain.com/api';

// Получить сообщения
fetch(`${baseUrl}/messages?limit=10`, {
  headers: {
    'X-API-Key': apiKey
  }
})
.then(response => response.json())
.then(data => console.log(data));

// Поиск
fetch(`${baseUrl}/search?q=привет`, {
  headers: {
    'X-API-Key': apiKey
  }
})
.then(response => response.json())
.then(data => console.log(data));
```

### cURL

```bash
# Получить статистику
curl -H "X-API-Key: your_api_key" \
     http://your-domain.com/api/stats

# Поиск сообщений
curl -H "X-API-Key: your_api_key" \
     "http://your-domain.com/api/search?q=привет&limit=5"
```

### Python (requests)

```python
import requests

api_key = 'your_api_key'
base_url = 'http://your-domain.com/api'

headers = {'X-API-Key': api_key}

# Получить каналы
response = requests.get(f'{base_url}/channels', headers=headers)
channels = response.json()

# Поиск
response = requests.get(f'{base_url}/search', 
                       params={'q': 'привет', 'limit': 10}, 
                       headers=headers)
results = response.json()
```

## Настройка API ключа

API ключ настраивается в файле `.env`:

```
API_KEY=your_secret_api_key_here
```

По умолчанию используется `default_api_key`.
