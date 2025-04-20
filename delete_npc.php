<?php
// delete_npc.php

session_start();
require 'db.php';

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Перенаправление на страницу логина, если не авторизован
    exit;
}

// Проверяем, был ли передан ID для удаления
if (isset($_POST['id'])) {
    $npc_id = $_POST['id'];

    // Выполняем удаление NPC/врага по переданному ID
    $sql = "DELETE FROM npc WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$npc_id]);

    // Перенаправляем обратно на страницу персонажей после удаления
    header('Location: characters.php');
    exit;
} else {
    // Если ID не передан, возвращаем на страницу персонажей
    header('Location: characters.php');
    exit;
}
?>
