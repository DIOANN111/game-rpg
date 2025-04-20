<?php
// character.php

session_start();
require 'db.php';

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Перенаправление на страницу логина, если не авторизован
    exit;
}

// Получаем информацию о пользователе из базы данных
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('Пользователь не найден.');
}

// Получаем тип фильтрации, если он был передан через GET
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';

// Формируем SQL-запрос с учетом фильтра
$sql = "SELECT * FROM npc";
if ($type_filter) {
    $sql .= " WHERE type = ?";
}

$stmt = $pdo->prepare($sql);
if ($type_filter) {
    $stmt->execute([$type_filter]);
} else {
    $stmt->execute();
}

$npc_list = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Персонажи</title>
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

        .npc-list {
            margin-top: 30px;
        }

        .npc-list a {
            display: block;
            margin: 10px 0;
            color: #fff;
            text-decoration: none;
            background: #3a3a3a;
            padding: 10px;
            border-radius: 8px;
            width: 250px;
            text-align: center;
        }

        .npc-list a:hover {
            background: #5c5c5c;
        }

        .delete-button {
            background: #ff4d4d;
            border: none;
            padding: 10px 20px;
            margin: 5px;
            cursor: pointer;
            color: white;
            border-radius: 5px;
        }

        .delete-button:hover {
            background: #ff3333;
        }

        .filter-buttons {
            margin-bottom: 20px;
        }

        .filter-button {
            background: #2a2a2a;
            padding: 10px 20px;
            margin: 5px;
            color: white;
            border-radius: 5px;
            text-decoration: none;
        }

        .filter-button:hover {
            background: #3c3c3c;
        }
    </style>
</head>
<body>

<h1>Персонажи</h1>

<div class="admin-buttons">
    <!-- Кнопки фильтрации -->
    <div class="filter-buttons">
        <a href="create_npc.php?type=npc" class="filter-button">NPC</a>
        <a href="create_enemy.php?type=enemy" class="filter-button">Враг</a>
        <a href="create_vendor.php?type=vendor" class="filter-button">Продавец</a>
    </div>

    <!-- Кнопка создания продавца -->
    <?php if ($type_filter == 'vendor'): ?>
        <a href="create_vendor.php" class="admin-button">Создать Продавца</a>
    <?php endif; ?>

    <!-- Список NPC/Врагов с кнопками для редактирования и удаления -->
    <div class="npc-list">
        <h2>Список NPC/Врагов</h2>
        <?php foreach ($npc_list as $npc): ?>
            <div>
                <a href="edit_npc.php?id=<?= $npc['id'] ?>" class="admin-button">Редактировать <?= htmlspecialchars($npc['name']) ?></a>

                <!-- Форма для удаления NPC/Врага -->
                <form action="delete_npc.php" method="POST" style="display:inline;">
                    <input type="hidden" name="id" value="<?= $npc['id'] ?>">
                    <button type="submit" class="delete-button" onclick="return confirm('Вы уверены, что хотите удалить этого персонажа?')">Удалить</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <a href="admin.php" class="admin-button">Назад</a>
</div>

</body>
</html>
