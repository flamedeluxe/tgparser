#!/bin/bash

echo "📋 Просмотр логов Docker контейнеров..."

# Показываем логи всех сервисов
docker-compose logs -f --tail=50
