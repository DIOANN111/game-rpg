<?php
// dialogs.php

session_start();
require 'db.php';

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Перенаправление на страницу логина, если не авторизован
    exit;
}

// Получаем список всех NPC
$sql = "SELECT * FROM npc";
$stmt = $pdo->query($sql);
$npcList = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Диалоги NPC</title>
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

        .npc-list {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .npc-item {
            background: #3a3a3a;
            padding: 15px 30px;
            margin: 10px;
            width: 250px;
            text-decoration: none;
            color: #fff;
            border-radius: 8px;
            cursor: pointer;
        }

        .npc-item:hover {
            background: #5c5c5c;
        }
    </style>
</head>
<body>

<h1>Диалоги NPC</h1>

<div class="npc-list">
    <?php foreach ($npcList as $npc): ?>
        <a href="create_dialog.php?npc_id=<?= $npc['id'] ?>" class="npc-item">Диалоги для <?= $npc['name'] ?></a>
    <?php endforeach; ?>
</div>

<a href="admin.php" class="admin-button">Назад</a>

</body>
</html>
