<?php
// update_activity.php

session_start();
require 'db.php';

// Проверка, что пользователь авторизован
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Обновляем время последней активности
$stmt = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);

// Перенаправление обратно на нужную страницу, например, на страницу игры
header('Location: game.php');
exit;
