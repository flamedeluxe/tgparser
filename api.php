<?php

require_once 'vendor/autoload.php';

use TgParser\Database;
use TgParser\ApiController;

// Загружаем переменные окружения
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Инициализируем базу данных
$database = new Database();

// Создаем API контроллер
$apiController = new ApiController($database);

// Обрабатываем API запрос
$apiController->handleRequest();
