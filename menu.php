<?php
// index.php

session_start();
require 'db.php';

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Перенаправление на страницу логина, если не авторизован
    exit;
}

// Обработка выхода из игры
if (isset($_GET['logout'])) {
    session_destroy();  // Уничтожаем сессию
    header('Location: login.php'); // Перенаправляем на страницу логина
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главное меню</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background-color: #1a1a1a;
            color: #00ff00;
            margin: 0;
            padding: 0;
        }

        .pipboy-container {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            border: 2px solid #00ff00;
            border-radius: 10px;
            background-color: #222;
            box-shadow: 0 0 15px rgba(0, 255, 0, 0.5);
        }

        .pipboy-header {
            display: flex;
            justify-content: space-between;
            padding: 15px;
            background-color: #333;
            border-bottom: 2px solid #00ff00;
        }

        .pipboy-logo {
            font-size: 24px;
            font-weight: bold;
            color: #00ff00;
        }

        .pipboy-time {
            font-size: 18px;
        }

        .pipboy-screen {
            padding: 20px;
            text-align: center;
        }

        .pipboy-screen h1 {
            font-size: 32px;
            margin-bottom: 20px;
        }

        .menu-buttons {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 50px;
        }

        .menu-button {
            background: #3a3a3a;
            padding: 15px 30px;
            margin: 10px;
            width: 250px;
            text-decoration: none;
            color: #00ff00;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
            text-align: center;
        }

        .menu-button:hover {
            background: #5c5c5c;
            color: #222;
        }

        .pipboy-footer {
            background-color: #333;
            padding: 15px;
            border-top: 2px solid #00ff00;
            display: flex;
            justify-content: space-between;
            font-size: 14px;
        }

        .footer-left {
            color: #a0a0a0;
        }

        .footer-right {
            text-align: right;
        }

        .footer-left a, .footer-right a {
            color: #00ff00;
            text-decoration: none;
        }

        .footer-left a:hover, .footer-right a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

<div class="pipboy-container">
    <div class="pipboy-header">
        <div class="pipboy-logo">Главное меню</div>
        <div class="pipboy-time"><?= date('H:i:s'); ?></div>
    </div>

    <div class="pipboy-screen">
        <h1>Главное меню</h1>

        <div class="menu-buttons">
            <!-- Кнопка "В игру" -->
            <a href="game.php" class="menu-button">В игру</a>

            <a href="online.php" class="menu-button">Кто онлайн</a>
            <!-- Кнопка "Персонаж" -->
            <a href="user.php?id=<?= htmlspecialchars($_SESSION['user_id']); ?>" class="menu-button">Персонаж</a>

            <!-- Кнопка "Новости" -->
            <a href="news.php" class="menu-button">Новости</a>

            <!-- Кнопка "Мои задания" -->
            <a href="quests_info.php" class="menu-button">Мои задания</a>

            <!-- Кнопка "Выход из игры" -->
            <a href="?logout=true" class="menu-button">Выход из игры</a>
        </div>
    </div>

    <div class="pipboy-footer">
        <div class="footer-left">
            <a href="#">Помощь</a> | <a href="#">О проекте</a>
        </div>
        <div class="footer-right">
            <a href="#">Контакты</a>
        </div>
    </div>
</div>

</body>

</html>
