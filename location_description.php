<?php
// location_description.php

session_start();
require 'db.php';

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Перенаправление на страницу логина, если не авторизован
    exit;
}

// Получаем ID локации из GET-параметра
if (isset($_GET['location_id'])) {
    $location_id = (int) $_GET['location_id'];

    // Запрос на получение данных локации по ID
    $stmt = $pdo->prepare("SELECT * FROM locations WHERE id = ?");
    $stmt->execute([$location_id]);
    $location = $stmt->fetch(PDO::FETCH_ASSOC);

    // Если локация не найдена, перенаправляем на главную
    if (!$location) {
        header('Location: index.php');
        exit;
    }
} else {
    // Если ID локации не передан, перенаправляем на главную
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/styles.css">
    <title>Описание локации</title>
    <style>

    </style>
</head>
<body>

<div class="pipboy-container">
    <div class="pipboy-header">
        <div class="pipboy-logo">Прах прошлого</div>
        <div class="pipboy-time">Время: <?= date("H:i:s") ?></div>
    </div>



    <div class="pipboy-screen">
        <h1><?= htmlspecialchars($location['name']) ?></h1>

        <div class="location-description">
            <p><strong></strong> <?= htmlspecialchars($location['description']) ?></p>
            <p><strong>Координаты:</strong> X: <?= htmlspecialchars($location['x']) ?>, Y: <?= htmlspecialchars($location['y']) ?>, Z: <?= htmlspecialchars($location['z']) ?></p>
        </div>

        <a class="back" href="game.php?location_id=<?= $location['id'] ?>">в игру</a>
    </div>

    <div class="pipboy-footer">
        <div class="footer-left">© 2025 Прах прошлого</div>

    </div>
</div>

</body>
</html>
