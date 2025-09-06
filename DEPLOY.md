# 🚀 Деплой на продакшн сервер

Руководство по развертыванию Telegram Parser Bot на сервере с Nginx + PHP + SQLite.

## 📋 Требования

- **Сервер**: Ubuntu 20.04+ с root доступом
- **Домен**: parser.dev-asgart.ru
- **IP**: 93.189.230.65
- **SSH**: доступ по ключу

## 🛠️ Пошаговая инструкция

### 1. Настройка DNS

Настройте DNS запись для домена:
```
A    parser.dev-asgart.ru    93.189.230.65
```

### 2. Первоначальная настройка сервера

```bash
# Подключитесь к серверу
ssh my

# Загрузите проект
git clone git@github.com:flamedeluxe/tgparser.git /tmp/tgparser
cd /tmp/tgparser

# Запустите настройку сервера
sudo ./server-setup.sh
```

### 3. Деплой приложения

```bash
# С локальной машины запустите деплой
./deploy.sh
```

### 4. Настройка SSL

```bash
# На сервере настройте SSL
ssh my
cd /var/www/tgparser
sudo ./ssl-setup.sh
```

## 📁 Структура на сервере

```
/var/www/tgparser/
├── public/              # Веб-корень
│   ├── index.php       # Главная страница
│   └── api.php         # API эндпоинт
├── data/               # База данных SQLite
│   └── bot_data.sqlite
├── logs/               # Логи
│   └── bot.log
├── storage/            # Временные файлы
├── src/                # Исходный код
├── docker/             # Docker конфигурации
├── .env                # Конфигурация
└── ...
```

## 🔧 Управление сервисами

### Бот

```bash
# Статус
sudo systemctl status tgparser-bot

# Запуск
sudo systemctl start tgparser-bot

# Остановка
sudo systemctl stop tgparser-bot

# Перезапуск
sudo systemctl restart tgparser-bot

# Логи
sudo journalctl -u tgparser-bot -f
```

### Nginx

```bash
# Статус
sudo systemctl status nginx

# Перезапуск
sudo systemctl reload nginx

# Проверка конфигурации
sudo nginx -t
```

## 📊 Мониторинг

### Логи

```bash
# Логи бота
tail -f /var/www/tgparser/logs/bot.log

# Логи Nginx
sudo tail -f /var/log/nginx/parser.dev-asgart.ru.access.log
sudo tail -f /var/log/nginx/parser.dev-asgart.ru.error.log

# Системные логи
sudo journalctl -u tgparser-bot -f
```

### Проверка API

```bash
# Статистика
curl -H "X-API-Key: prod_api_key_2024_secure" https://parser.dev-asgart.ru/api/stats

# Каналы
curl -H "X-API-Key: prod_api_key_2024_secure" https://parser.dev-asgart.ru/api/channels

# Поиск
curl -H "X-API-Key: prod_api_key_2024_secure" "https://parser.dev-asgart.ru/api/search?q=test"
```

## 🔄 Обновление

```bash
# Обновление кода
git pull origin main

# Перезапуск сервисов
sudo systemctl restart tgparser-bot
sudo systemctl reload nginx
```

## 🔒 Безопасность

### Firewall

```bash
# Проверка статуса
sudo ufw status

# Открытые порты
sudo ufw allow 22    # SSH
sudo ufw allow 80    # HTTP
sudo ufw allow 443   # HTTPS
```

### SSL

```bash
# Проверка сертификата
sudo certbot certificates

# Обновление сертификата
sudo certbot renew --dry-run
```

## 🚨 Устранение неполадок

### Проблемы с ботом

1. **Бот не запускается**:
   ```bash
   sudo systemctl status tgparser-bot
   sudo journalctl -u tgparser-bot -n 50
   ```

2. **Ошибки в логах**:
   ```bash
   tail -f /var/www/tgparser/logs/bot.log
   ```

3. **Проблемы с правами**:
   ```bash
   sudo chown -R www-data:www-data /var/www/tgparser
   sudo chmod -R 755 /var/www/tgparser
   ```

### Проблемы с Nginx

1. **Ошибки конфигурации**:
   ```bash
   sudo nginx -t
   ```

2. **Проблемы с SSL**:
   ```bash
   sudo certbot certificates
   sudo systemctl status nginx
   ```

### Проблемы с API

1. **API не отвечает**:
   ```bash
   curl -I https://parser.dev-asgart.ru/api/stats
   ```

2. **Ошибка 401**:
   - Проверьте API ключ в запросе
   - Убедитесь, что ключ совпадает с настройками

## 📈 Производительность

### Оптимизация Nginx

```nginx
# В nginx-production.conf
worker_processes auto;
worker_connections 1024;

# Gzip
gzip on;
gzip_types text/plain application/json;
```

### Мониторинг ресурсов

```bash
# Использование CPU и памяти
htop

# Использование диска
df -h

# Сетевые соединения
netstat -tulpn
```

## 🔄 Резервное копирование

### База данных

```bash
# Создание бэкапа
cp /var/www/tgparser/data/bot_data.sqlite /backup/bot_data_$(date +%Y%m%d_%H%M%S).sqlite

# Восстановление
cp /backup/bot_data_20241201_120000.sqlite /var/www/tgparser/data/bot_data.sqlite
```

### Автоматическое резервное копирование

```bash
# Добавить в crontab
0 2 * * * cp /var/www/tgparser/data/bot_data.sqlite /backup/bot_data_$(date +\%Y\%m\%d_\%H\%M\%S).sqlite
```

## 📞 Поддержка

При возникновении проблем:

1. Проверьте логи сервисов
2. Убедитесь в правильности конфигурации
3. Проверьте статус всех сервисов
4. Обратитесь к документации проекта
