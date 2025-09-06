#!/bin/bash

# Скрипт первоначальной настройки сервера
# Запускать на сервере

set -e

echo "🛠️ Настройка сервера для Telegram Parser Bot..."

# Обновляем систему
echo "📦 Обновление системы..."
sudo apt update && sudo apt upgrade -y

# Устанавливаем необходимые пакеты
echo "📚 Установка пакетов..."
sudo apt install -y nginx php8.1-fpm php8.1-cli php8.1-sqlite3 php8.1-curl php8.1-mbstring php8.1-xml composer git unzip

# Настраиваем PHP-FPM
echo "🐘 Настройка PHP-FPM..."
sudo sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /etc/php/8.1/fpm/php.ini
sudo systemctl restart php8.1-fpm

# Создаем пользователя для проекта
echo "👤 Настройка пользователей..."
sudo useradd -m -s /bin/bash tgparser || echo "Пользователь уже существует"

# Создаем директории
echo "📁 Создание директорий..."
sudo mkdir -p /var/www/tgparser/{public,data,logs,storage}
sudo chown -R www-data:www-data /var/www/tgparser
sudo chmod -R 755 /var/www/tgparser

# Настраиваем Nginx
echo "🌐 Настройка Nginx..."
sudo rm -f /etc/nginx/sites-enabled/default

# Создаем временную конфигурацию для получения SSL
sudo tee /etc/nginx/sites-available/parser.dev-asgart.ru > /dev/null <<EOF
server {
    listen 80;
    server_name parser.dev-asgart.ru;
    root /var/www/tgparser/public;
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php\$is_args\$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }
}
EOF

sudo ln -sf /etc/nginx/sites-available/parser.dev-asgart.ru /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx

# Настраиваем firewall
echo "🔥 Настройка firewall..."
sudo ufw allow 22
sudo ufw allow 80
sudo ufw allow 443
sudo ufw --force enable

# Устанавливаем Certbot
echo "🔒 Установка Certbot..."
sudo apt install -y certbot python3-certbot-nginx

echo "✅ Сервер настроен!"
echo "🌐 Домен: parser.dev-asgart.ru"
echo "📁 Директория: /var/www/tgparser"
echo ""
echo "Следующие шаги:"
echo "1. Настройте DNS для parser.dev-asgart.ru -> 93.189.230.65"
echo "2. Запустите: ./deploy.sh"
echo "3. Настройте SSL: ./ssl-setup.sh"
