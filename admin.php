<?php
require_once 'vendor/autoload.php';

// Загружаем переменные окружения
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Инициализируем базу данных
$database = new TgParser\Database();

// Получаем список каналов
$channels = $database->getChannels();

// Получаем статистику
$stats = $database->getStats();

// Обработка AJAX запросов
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_messages':
            $channel_id = $_GET['channel_id'] ?? '';
            $limit = $_GET['limit'] ?? 50;
            $offset = $_GET['offset'] ?? 0;
            $search = $_GET['search'] ?? '';
            
            if ($search) {
                $messages = $database->searchMessages($search, $limit, $offset);
            } else {
                $messages = $database->getMessagesByChannel($channel_id, $limit, $offset);
            }
            
            echo json_encode([
                'success' => true,
                'data' => $messages
            ]);
            exit;
            
        case 'get_stats':
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админка - Telegram Parser</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .channel-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .channel-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .message-item {
            border-left: 3px solid #007bff;
            padding-left: 15px;
            margin-bottom: 15px;
        }
        .message-text {
            white-space: pre-wrap;
            word-break: break-word;
        }
        .media-badge {
            font-size: 0.8em;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .loading {
            display: none;
        }
        .search-box {
            position: sticky;
            top: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">
                <i class="fab fa-telegram"></i> Telegram Parser Admin
            </span>
            <div class="d-flex">
                <span class="navbar-text me-3">
                    <i class="fas fa-robot"></i> @tgparser2_bot
                </span>
                <button class="btn btn-outline-light btn-sm" onclick="refreshStats()">
                    <i class="fas fa-sync-alt"></i> Обновить
                </button>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- Статистика -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-comments fa-2x mb-2"></i>
                        <h3 id="total-messages"><?= $stats['total_messages'] ?></h3>
                        <p class="mb-0">Сообщений</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-broadcast-tower fa-2x mb-2"></i>
                        <h3 id="total-channels"><?= $stats['total_channels'] ?></h3>
                        <p class="mb-0">Каналов</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <h5 id="last-parsing"><?= $stats['last_parsing'] ?></h5>
                        <p class="mb-0">Последний парсинг</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Список каналов -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list"></i> Каналы</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($channels as $channel): ?>
                            <div class="list-group-item channel-card" 
                                 onclick="loadChannelMessages('<?= $channel['chat_id'] ?>', '<?= htmlspecialchars($channel['chat_title']) ?>')">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= htmlspecialchars($channel['chat_title']) ?></h6>
                                    <small class="text-muted"><?= $channel['chat_type'] ?></small>
                                </div>
                                <p class="mb-1 text-muted">ID: <?= $channel['chat_id'] ?></p>
                                <small>Добавлен: <?= date('d.m.Y H:i', strtotime($channel['created_at'])) ?></small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Сообщения -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 id="messages-title"><i class="fas fa-comments"></i> Выберите канал</h5>
                        <div class="search-box">
                            <div class="input-group">
                                <input type="text" class="form-control" id="search-input" placeholder="Поиск по сообщениям...">
                                <button class="btn btn-outline-secondary" onclick="searchMessages()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="messages-container">
                            <div class="text-center text-muted">
                                <i class="fas fa-hand-pointer fa-3x mb-3"></i>
                                <p>Выберите канал для просмотра сообщений</p>
                            </div>
                        </div>
                        <div class="loading text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Загрузка...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentChannelId = null;
        let currentOffset = 0;
        const limit = 50;

        function loadChannelMessages(channelId, channelTitle) {
            currentChannelId = channelId;
            currentOffset = 0;
            
            document.getElementById('messages-title').innerHTML = 
                `<i class="fas fa-comments"></i> ${channelTitle}`;
            
            showLoading(true);
            
            fetch(`admin.php?action=get_messages&channel_id=${channelId}&limit=${limit}&offset=0`)
                .then(response => response.json())
                .then(data => {
                    showLoading(false);
                    if (data.success) {
                        displayMessages(data.data);
                    } else {
                        showError('Ошибка загрузки сообщений');
                    }
                })
                .catch(error => {
                    showLoading(false);
                    showError('Ошибка: ' + error.message);
                });
        }

        function searchMessages() {
            const searchQuery = document.getElementById('search-input').value;
            if (!searchQuery.trim()) return;
            
            showLoading(true);
            
            fetch(`admin.php?action=get_messages&search=${encodeURIComponent(searchQuery)}&limit=${limit}&offset=0`)
                .then(response => response.json())
                .then(data => {
                    showLoading(false);
                    if (data.success) {
                        displayMessages(data.data, true);
                        document.getElementById('messages-title').innerHTML = 
                            `<i class="fas fa-search"></i> Результаты поиска: "${searchQuery}"`;
                    } else {
                        showError('Ошибка поиска');
                    }
                })
                .catch(error => {
                    showLoading(false);
                    showError('Ошибка: ' + error.message);
                });
        }

        function displayMessages(messages, isSearch = false) {
            const container = document.getElementById('messages-container');
            
            if (messages.length === 0) {
                container.innerHTML = `
                    <div class="text-center text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>${isSearch ? 'Сообщения не найдены' : 'В этом канале пока нет сообщений'}</p>
                    </div>
                `;
                return;
            }

            let html = '';
            messages.forEach(message => {
                const date = new Date(message.message_date * 1000).toLocaleString('ru-RU');
                const mediaBadge = message.media_type ? 
                    `<span class="badge bg-info media-badge">${message.media_type}</span>` : '';
                
                html += `
                    <div class="message-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="message-text">${escapeHtml(message.text_content || message.caption || '')}</div>
                                ${mediaBadge}
                            </div>
                            <div class="text-muted small ms-3">
                                <div>${date}</div>
                                <div>ID: ${message.message_id}</div>
                            </div>
                        </div>
                        <div class="text-muted small mt-1">
                            <i class="fas fa-user"></i> ${message.from_user} | 
                            <i class="fas fa-broadcast-tower"></i> ${message.chat_title}
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        function showLoading(show) {
            document.querySelector('.loading').style.display = show ? 'block' : 'none';
            document.getElementById('messages-container').style.display = show ? 'none' : 'block';
        }

        function showError(message) {
            document.getElementById('messages-container').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> ${message}
                </div>
            `;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function refreshStats() {
            fetch('admin.php?action=get_stats')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('total-messages').textContent = data.data.total_messages;
                        document.getElementById('total-channels').textContent = data.data.total_channels;
                        document.getElementById('last-parsing').textContent = data.data.last_parsing;
                    }
                });
        }

        // Поиск по Enter
        document.getElementById('search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchMessages();
            }
        });

        // Автообновление статистики каждые 30 секунд
        setInterval(refreshStats, 30000);
    </script>
</body>
</html>
