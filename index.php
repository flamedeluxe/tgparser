<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram Parser Bot</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }
        
        .header h1 {
            font-size: 3rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }
        
        .card p {
            margin-bottom: 20px;
            color: #666;
        }
        
        .btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s ease;
        }
        
        .btn:hover {
            background: #5a6fd8;
        }
        
        .api-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .api-section h2 {
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .endpoint {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 10px 0;
            border-radius: 0 5px 5px 0;
        }
        
        .method {
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: bold;
        }
        
        .status.online {
            background: #d4edda;
            color: #155724;
        }
        
        .status.offline {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🤖 Telegram Parser Bot</h1>
            <p>Автоматический парсинг контента из Telegram каналов с REST API</p>
        </div>
        
        <div class="cards">
            <div class="card">
                <h3>📊 Статистика</h3>
                <p>Просматривайте статистику парсинга в реальном времени</p>
                <a href="/api/stats" class="btn">Посмотреть статистику</a>
            </div>
            
            <div class="card">
                <h3>📺 Каналы</h3>
                <p>Список всех подключенных каналов и групп</p>
                <a href="/api/channels" class="btn">Список каналов</a>
            </div>
            
            <div class="card">
                <h3>🔍 Поиск</h3>
                <p>Поиск по всем сохраненным сообщениям</p>
                <a href="/api/search?q=пример" class="btn">Попробовать поиск</a>
            </div>
            
            <div class="card">
                <h3>📚 Документация</h3>
                <p>Полная документация API с примерами</p>
                <a href="/API.md" class="btn">Открыть документацию</a>
            </div>
        </div>
        
        <div class="api-section">
            <h2>🌐 API Endpoints</h2>
            
            <div class="endpoint">
                <span class="method">GET</span> <strong>/api/stats</strong>
                <p>Получение статистики парсинга</p>
            </div>
            
            <div class="endpoint">
                <span class="method">GET</span> <strong>/api/channels</strong>
                <p>Список всех каналов и групп</p>
            </div>
            
            <div class="endpoint">
                <span class="method">GET</span> <strong>/api/messages</strong>
                <p>Получение сообщений с фильтрацией</p>
            </div>
            
            <div class="endpoint">
                <span class="method">GET</span> <strong>/api/search</strong>
                <p>Поиск по тексту сообщений</p>
            </div>
            
            <h3 style="margin-top: 30px;">🔑 Аутентификация</h3>
            <p>Все API запросы требуют API ключ:</p>
            <code>X-API-Key: your_api_key</code>
            
            <h3 style="margin-top: 20px;">📖 Примеры использования</h3>
            <pre style="background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;">
# Получить статистику
curl -H "X-API-Key: default_api_key" http://localhost/api/stats

# Поиск сообщений
curl -H "X-API-Key: default_api_key" "http://localhost/api/search?q=привет"

# Получить каналы
curl -H "X-API-Key: default_api_key" http://localhost/api/channels
            </pre>
        </div>
    </div>
    
    <script>
        // Проверка статуса API
        fetch('/api/stats', {
            headers: {
                'X-API-Key': 'default_api_key'
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('API Status:', data.success ? 'Online' : 'Offline');
        })
        .catch(error => {
            console.log('API Status: Offline');
        });
    </script>
</body>
</html>
