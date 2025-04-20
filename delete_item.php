<?php
// delete_item.php

session_start();
require 'db.php';

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Перенаправление на страницу логина, если не авторизован
    exit;
}

// Обработка удаления предмета
if (isset($_GET['item_id'])) {
    $item_id = (int) $_GET['item_id'];

    // Удаляем предмет из базы данных
    $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
    $stmt->execute([$item_id]);

    // Перенаправляем на страницу предметов после удаления
    header('Location: items.php');
    exit;
}

// Получаем список предметов из базы данных
$stmt = $pdo->query("SELECT * FROM items");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Удалить предмет</title>
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

        .items-list {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 30px;
        }

        .item {
            background: #3a3a3a;
            padding: 15px;
            margin: 10px;
            width: 250px;
            text-decoration: none;
            color: #fff;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }

        .item:hover {
            background: #5c5c5c;
        }

        .admin-buttons {
            margin-top: 30px;
        }

        .back {
            display: block;
            margin-top: 30px;
            color: #fff;
            text-decoration: none;
            background: #3a3a3a;
            padding: 10px 20px;
            margin: 10px;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            transition: 0.3s;
        }

        .back:hover {
            background: #5c5c5c;
        }
    </style>
</head>
<body>

<h1>Удалить предмет</h1>

<div class="items-list">
    <?php foreach ($items as $item): ?>
        <div class="item">
            <h3><?= htmlspecialchars($item['name']) ?></h3>
            <p><?= htmlspecialchars($item['description']) ?></p>
            <a href="delete_item.php?item_id=<?= $item['id'] ?>" class="item">Удалить</a>
        </div>
    <?php endforeach; ?>
</div>

<!-- Кнопка "Меню" -->
<a class="back" href="index.php">Меню</a>

</body>
</html>
