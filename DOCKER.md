# Docker Deployment Guide

Руководство по развертыванию Telegram Parser Bot с использованием Docker и Nginx.

## 🐳 Архитектура

Проект использует следующую архитектуру:

- **Nginx** - веб-сервер и reverse proxy
- **PHP-FPM** - обработка PHP запросов
- **SQLite** - база данных (файл)
- **Redis** - кеширование (опционально)
- **Adminer** - веб-интерфейс для управления БД

## 🚀 Быстрый старт

### 1. Клонирование и запуск

```bash
# Клонируйте репозиторий
git clone <repository-url>
cd tgparser

# Запустите все сервисы
docker-compose up -d

# Проверьте статус
docker-compose ps
```

### 2. Проверка работы

- **API**: http://localhost/api/stats
- **Главная страница**: http://localhost
- **Adminer**: http://localhost:8080
- **Документация**: http://localhost/API.md

## 📁 Структура Docker файлов

```
docker/
├── nginx.conf          # Конфигурация Nginx
├── nginx-main.conf     # Основная конфигурация Nginx
├── php-fpm.conf        # Конфигурация PHP-FPM
└── start.sh            # Скрипт запуска контейнера
```

## ⚙️ Конфигурация

### Переменные окружения

Создайте файл `.env`:

```env
# Telegram Bot
BOT_TOKEN=your_bot_token
BOT_USERNAME=your_bot_username

# API
API_KEY=your_secret_api_key

# База данных
DATABASE_FILE=bot_data.sqlite

# Логирование
LOG_LEVEL=info
```

### Порты

- **80** - Nginx (API и веб-интерфейс)
- **8080** - Adminer (управление БД)
- **6379** - Redis (кеширование)

## 🔧 Управление сервисами

### Основные команды

```bash
# Запуск всех сервисов
docker-compose up -d

# Остановка всех сервисов
docker-compose down

# Перезапуск сервисов
docker-compose restart

# Просмотр логов
docker-compose logs -f

# Просмотр статуса
docker-compose ps
```

### Удобные скрипты

```bash
# Сборка образа
./docker-build.sh

# Просмотр логов
./docker-logs.sh

# Подключение к контейнеру
./docker-shell.sh
```

## 🗄️ Управление данными

### База данных

База данных SQLite монтируется как volume:

```yaml
volumes:
  - ./bot_data.sqlite:/var/www/html/bot_data.sqlite
```

### Логи

Логи сохраняются в директории `logs/`:

```yaml
volumes:
  - ./logs:/var/log/nginx
```

### Резервное копирование

```bash
# Создание бэкапа БД
cp bot_data.sqlite backup_$(date +%Y%m%d_%H%M%S).sqlite

# Восстановление из бэкапа
cp backup_20231201_120000.sqlite bot_data.sqlite
```

## 🔍 Мониторинг и отладка

### Просмотр логов

```bash
# Все сервисы
docker-compose logs -f

# Конкретный сервис
docker-compose logs -f app
docker-compose logs -f bot
```

### Подключение к контейнеру

```bash
# Подключение к приложению
docker-compose exec app sh

# Подключение к боту
docker-compose exec bot sh
```

### Проверка статуса

```bash
# Статус контейнеров
docker-compose ps

# Использование ресурсов
docker stats

# Проверка API
curl -H "X-API-Key: default_api_key" http://localhost/api/stats
```

## 🚨 Устранение неполадок

### Проблемы с запуском

1. **Порт занят**:
   ```bash
   # Проверьте занятые порты
   lsof -i :80
   lsof -i :8080
   
   # Измените порты в docker-compose.yml
   ```

2. **Ошибки прав доступа**:
   ```bash
   # Установите правильные права
   chmod -R 755 .
   chown -R $USER:$USER .
   ```

3. **Проблемы с базой данных**:
   ```bash
   # Пересоздайте БД
   rm bot_data.sqlite
   docker-compose restart
   ```

### Проблемы с API

1. **API не отвечает**:
   ```bash
   # Проверьте логи
   docker-compose logs app
   
   # Перезапустите сервис
   docker-compose restart app
   ```

2. **Ошибка 401 (Unauthorized)**:
   - Проверьте API ключ в запросе
   - Убедитесь, что ключ совпадает с настройками

## 📊 Производительность

### Оптимизация Nginx

```nginx
# В docker/nginx.conf
worker_processes auto;
worker_connections 1024;

# Gzip сжатие
gzip on;
gzip_types text/plain application/json;
```

### Оптимизация PHP-FPM

```ini
# В docker/php-fpm.conf
pm = dynamic
pm.max_children = 10
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
```

## 🔒 Безопасность

### Рекомендации

1. **Измените API ключ**:
   ```env
   API_KEY=your_very_secure_api_key_here
   ```

2. **Ограничьте доступ к Adminer**:
   - Используйте firewall
   - Настройте аутентификацию

3. **Регулярно обновляйте образы**:
   ```bash
   docker-compose pull
   docker-compose up -d
   ```

## 🌐 Продакшн развертывание

### Настройка для продакшна

1. **Используйте внешнюю БД**:
   ```yaml
   # Замените SQLite на PostgreSQL/MySQL
   services:
     postgres:
       image: postgres:13
       environment:
         POSTGRES_DB: tgparser
         POSTGRES_USER: user
         POSTGRES_PASSWORD: password
   ```

2. **Настройте SSL**:
   ```yaml
   # Добавьте SSL сертификаты
   volumes:
     - ./ssl:/etc/nginx/ssl
   ```

3. **Используйте внешний Redis**:
   ```yaml
   # Подключите к внешнему Redis
   environment:
     REDIS_HOST: redis.example.com
     REDIS_PORT: 6379
   ```

### Мониторинг

```bash
# Установите мониторинг
docker run -d \
  --name watchtower \
  -v /var/run/docker.sock:/var/run/docker.sock \
  containrrr/watchtower
```

## 📚 Дополнительные ресурсы

- [Docker Compose документация](https://docs.docker.com/compose/)
- [Nginx документация](https://nginx.org/en/docs/)
- [PHP-FPM документация](https://www.php.net/manual/en/install.fpm.php)
- [SQLite документация](https://www.sqlite.org/docs.html)
