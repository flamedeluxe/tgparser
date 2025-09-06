# 🤖 Telegram Parser Bot

[![Docker](https://img.shields.io/badge/Docker-Ready-blue?logo=docker)](https://www.docker.com/)
[![PHP](https://img.shields.io/badge/PHP-8.1+-purple?logo=php)](https://www.php.net/)
[![SQLite](https://img.shields.io/badge/Database-SQLite-green?logo=sqlite)](https://www.sqlite.org/)
[![API](https://img.shields.io/badge/API-REST-orange?logo=api)](https://en.wikipedia.org/wiki/Representational_state_transfer)

Автоматический парсинг контента из Telegram каналов с REST API, веб-интерфейсом и Docker поддержкой.

## ✨ Возможности

- 🤖 **Автоматический парсинг** контента из каналов и групп
- 📝 **Сохранение** текстовых сообщений в SQLite базу данных
- 🎬 **Парсинг медиафайлов** (фото, видео, документы, аудио)
- 📊 **Статистика парсинга** в реальном времени
- 🔍 **Полнотекстовый поиск** по сообщениям
- 📺 **Управление каналами** и группами
- 🌐 **REST API** для доступа к данным
- 🐳 **Docker Compose** с Nginx
- 📱 **Веб-интерфейс** для управления

## 🚀 Быстрый старт

### Docker (Рекомендуется)

```bash
# Клонируйте репозиторий
git clone git@github.com:flamedeluxe/tgparser.git
cd tgparser

# Запустите все сервисы
docker-compose up -d

# Проверьте статус
docker-compose ps
```

**Доступные сервисы:**
- 🌐 **API**: http://localhost/api
- 🏠 **Главная**: http://localhost
- 🗄️ **Adminer**: http://localhost:8080
- 📚 **Документация**: http://localhost/API.md

### Локальная установка

```bash
# Установите зависимости
composer install

# Инициализируйте базу данных
php init_db.php

# Запустите бота
php bot.php

# Запустите API сервер
./start_api.sh
```

## 📖 Документация

- 📚 [Полная документация](README.md)
- 🌐 [API документация](API.md)
- 🐳 [Docker руководство](DOCKER.md)

## 🔧 Настройка

1. Скопируйте конфигурацию:
```bash
cp config.env .env
```

2. Отредактируйте `.env`:
```env
BOT_TOKEN=your_telegram_bot_token
BOT_USERNAME=your_bot_username
API_KEY=your_secret_api_key
```

## 📊 API Endpoints

| Метод | Endpoint | Описание |
|-------|----------|----------|
| `GET` | `/api/stats` | Статистика парсинга |
| `GET` | `/api/channels` | Список каналов |
| `GET` | `/api/messages` | Получение сообщений |
| `GET` | `/api/search` | Поиск по сообщениям |

### Пример использования API

```bash
# Получить статистику
curl -H "X-API-Key: default_api_key" http://localhost/api/stats

# Поиск сообщений
curl -H "X-API-Key: default_api_key" "http://localhost/api/search?q=привет"

# Получить каналы
curl -H "X-API-Key: default_api_key" http://localhost/api/channels
```

## 🐳 Docker команды

```bash
# Запуск всех сервисов
docker-compose up -d

# Просмотр логов
docker-compose logs -f

# Остановка
docker-compose down

# Перезапуск
docker-compose restart

# Сборка образа
./docker-build.sh
```

## 📁 Структура проекта

```
tgparser/
├── 🤖 bot.php              # Основной файл бота
├── 🌐 api.php              # API сервер
├── 🏠 index.php            # Главная страница
├── 🐳 Dockerfile           # Docker образ
├── 🐳 docker-compose.yml   # Docker Compose
├── 📚 README.md            # Документация
├── 🌐 API.md              # API документация
├── 🐳 DOCKER.md           # Docker руководство
├── 📁 docker/             # Docker конфигурации
├── 📁 src/                # Исходный код
│   ├── Database.php       # Работа с БД
│   └── ApiController.php  # API контроллер
└── 📁 logs/               # Логи
```

## 🔒 Безопасность

- 🔑 **API ключ** аутентификация
- 🛡️ **CORS** поддержка
- 🔒 **Защита** служебных файлов
- 🚫 **Ограничение** доступа к БД

## 📈 Мониторинг

- 📊 **Статистика** в реальном времени
- 📝 **Логирование** всех операций
- 🗄️ **Adminer** для управления БД
- 🔍 **Поиск** по всем данным

## 🤝 Вклад в проект

1. Форкните репозиторий
2. Создайте ветку для новой функции
3. Внесите изменения
4. Создайте Pull Request

## 📄 Лицензия

MIT License - см. файл [LICENSE](LICENSE)

## 🆘 Поддержка

Если у вас есть вопросы или проблемы:

1. Проверьте [документацию](README.md)
2. Создайте [Issue](https://github.com/flamedeluxe/tgparser/issues)
3. Обратитесь к [Docker руководству](DOCKER.md)

## 🎯 Roadmap

- [ ] Поддержка PostgreSQL/MySQL
- [ ] Веб-интерфейс для управления
- [ ] Экспорт данных
- [ ] Уведомления
- [ ] Аналитика и графики

---

⭐ **Если проект полезен, поставьте звезду!**
