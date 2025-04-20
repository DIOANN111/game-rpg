<?php
// items.php

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
    <title>Предметы</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #1e1e1e;
            color: #e0e0e0;
            text-align: center;
            margin: 0;
            padding: 0;
        }

        h1 {
            margin: 20px 0;
            font-size: 32px;
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
            color: #fff;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }

        .admin-button:hover {
            background: #5c5c5c;
        }

        .admin-buttons a {
            margin-bottom: 20px; /* Увеличиваем отступ между кнопками */
        }

        .message {
            background-color: #4caf50;
            color: white;
            padding: 15px;
            margin: 20px auto;
            width: 80%;
            border-radius: 5px;
            font-size: 18px;
        }
    </style>
</head>
<body>

<h1>Предметы</h1>

<?php
// Проверка и вывод уведомления, если оно есть в сессии
if (isset($_SESSION['message'])) {
    echo '<div class="message">' . $_SESSION['message'] . '</div>';
    unset($_SESSION['message']); // Удаляем сообщение после его отображения
}
?>

<div class="admin-buttons">
    <a href="create_item.php" class="admin-button">Создать предмет</a> <!-- Ссылка для создания предмета -->
    <a href="delete_item.php" class="admin-button">Удалить предмет</a> <!-- Ссылка для удаления предмета -->
    <a href="admin.php" class="admin-button">Назад</a>
    <a href="game.php" class="admin-button">В игру</a>
</div>

</body>
</html>
