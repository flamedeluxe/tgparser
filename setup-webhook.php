<?php

require_once 'vendor/autoload.php';

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Exception\TelegramException;

// Загружаем переменные окружения
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$bot_token = $_ENV['BOT_TOKEN'] ?? '7871178627:AAFY2IYhkaf0GdlDBRC8Tvg26SXRMMVvwi0';
$bot_username = $_ENV['BOT_USERNAME'] ?? 'tgparser2_bot';
$webhook_url = $_ENV['WEBHOOK_URL'] ?? 'https://parser.dev-asgart.ru/webhook.php';

try {
    // Создаем экземпляр бота
    $telegram = new Telegram($bot_token, $bot_username);
    
    echo "🔧 Настройка webhook для бота @{$bot_username}...\n";
    echo "🌐 URL: {$webhook_url}\n\n";
    
    // Удаляем старый webhook
    echo "🗑️ Удаление старого webhook...\n";
    $result = Request::deleteWebhook();
    if ($result->isOk()) {
        echo "✅ Старый webhook удален\n";
    } else {
        echo "⚠️ Ошибка при удалении webhook: " . $result->getDescription() . "\n";
    }
    
    // Устанавливаем новый webhook
    echo "🔗 Установка нового webhook...\n";
    $result = Request::setWebhook([
        'url' => $webhook_url,
        'max_connections' => 40,
        'allowed_updates' => ['message', 'channel_post']
    ]);
    
    if ($result->isOk()) {
        echo "✅ Webhook успешно установлен!\n";
        echo "🌐 URL: {$webhook_url}\n";
        echo "📊 Максимум соединений: 40\n";
        echo "📝 Обновления: message, channel_post\n";
    } else {
        echo "❌ Ошибка при установке webhook: " . $result->getDescription() . "\n";
        exit(1);
    }
    
    // Проверяем информацию о webhook
    echo "\n🔍 Проверка информации о webhook...\n";
    $result = Request::getWebhookInfo();
    if ($result->isOk()) {
        $webhook_info = $result->getResult();
        echo "✅ Webhook активен: " . ($webhook_info->getUrl() ? 'Да' : 'Нет') . "\n";
        echo "🌐 URL: " . $webhook_info->getUrl() . "\n";
        echo "📊 Ожидающих обновлений: " . $webhook_info->getPendingUpdateCount() . "\n";
        echo "❌ Последняя ошибка: " . ($webhook_info->getLastErrorMessage() ?: 'Нет') . "\n";
        echo "📅 Последняя ошибка: " . ($webhook_info->getLastErrorDate() ? date('Y-m-d H:i:s', $webhook_info->getLastErrorDate()) : 'Нет') . "\n";
    }
    
    echo "\n🎉 Настройка webhook завершена!\n";
    echo "🤖 Бот готов к работе через webhook\n";
    
} catch (TelegramException $e) {
    echo "❌ Ошибка Telegram: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Общая ошибка: " . $e->getMessage() . "\n";
    exit(1);
}
