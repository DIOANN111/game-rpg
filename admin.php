<?php
// admin.php

session_start();
require 'db.php';

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Перенаправление на страницу логина, если не авторизован
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Администрация</title>
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

        .pipboy-nav {
            background-color: #333;
            padding: 10px;
            text-align: center;
        }

        .nav-button {
            margin: 0 10px;
            text-decoration: none;
            color: #00ff00;
            font-size: 16px;
            font-weight: bold;
        }

        .nav-button:hover {
            color: #222;
            background-color: #00ff00;
            padding: 5px 10px;
            border-radius: 5px;
        }

        .pipboy-screen {
            padding: 20px;
            text-align: center;
        }

        .pipboy-screen h1 {
            font-size: 32px;
            margin-bottom: 20px;
        }

        .admin-buttons {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 50px;
        }

        .admin-button {
            background: #3a3a3a;
            padding: 15px 30px;
            margin: 10px;
            width: 250px;
            text-decoration: none;
            color: #00ff00;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }

        .admin-button:hover {
            background: #5c5c5c;
        }

        /* Отступ между кнопками */
        .admin-buttons a {
            margin-bottom: 20px; /* Увеличиваем отступ между кнопками */
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
    <header class="pipboy-header">
        <div class="pipboy-logo">
            <a href="admin.php" style="text-decoration: none; color: #00ff00;">NOVA-X</a>
        </div>
        <div class="pipboy-time">
            <!-- Вы можете добавить сюда отображение времени -->
        </div>
    </header>

    <main class="pipboy-screen">
        <h1>Администрация</h1>

        <div class="admin-buttons">
            <a href="locations.php" class="admin-button">Локации</a>
            <a href="characters.php" class="admin-button">Персонажи</a>
            <a href="quests.php" class="admin-button">Квесты</a>
            <a href="items.php" class="admin-button">Предметы</a>
            <a href="dialogs.php" class="admin-button">Диалоги</a> <!-- Добавляем кнопку для диалогов -->

            <!-- Кнопка "Меню" с тем же размером, что и другие -->
            <a href="game.php" class="admin-button">В игру</a>
        </div>
    </main>

    <footer class="pipboy-footer">
        <div class="footer-left">
            <!-- Здесь могут быть ссылки на другие разделы -->
        </div>
        <div class="footer-right">
            <!-- Можно добавить кнопки или другие ссылки -->
        </div>
    </footer>
</div>
</body>
</html>
