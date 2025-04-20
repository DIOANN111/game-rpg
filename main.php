<?php
session_start(); // Начинаем сессию

// Если пользователь уже авторизован, перенаправляем его на страницу игры
if (isset($_SESSION['user_id'])) {
    header("Location: game.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная страница</title>
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

        .menu {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 30px;
        }

        .menu a {
            display: block;
            width: 250px;
            padding: 15px;
            margin: 10px;
            text-decoration: none;
            font-size: 18px;
            color: #fff;
            background: #3a3a3a;
            border-radius: 8px;
            transition: 0.3s;
        }

        .menu a:hover {
            background: #5c5c5c;
        }
    </style>
</head>
<body>

<h1>Добро пожаловать!</h1>

<div class="menu">
    <a href="register.php">Зарегистрироваться</a>
    <a href="login.php">Войти</a>
</div>

</body>
</html>
