<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Вы должны войти в систему, чтобы принять квест.");
}

$user_id = $_SESSION['user_id'];
$quest_id = $_POST['quest_id'] ?? null;

if (!$quest_id) {
    die("Некорректный запрос.");
}

// Получаем данные о квесте
$stmt = $pdo->prepare("SELECT * FROM quests WHERE id = ?");
$stmt->execute([$quest_id]);
$quest = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quest) {
    die("Квест не найден.");
}

// Проверяем, не взят ли уже этот квест
$stmt = $pdo->prepare("SELECT current_quest_id FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$current_quest_id = $stmt->fetchColumn();

if ($current_quest_id) {
    die("Вы уже приняли этот квест.");
}

// Обновляем таблицу users, добавляя текущий квест
$stmt = $pdo->prepare("UPDATE users SET current_quest_id = ? WHERE user_id = ?");
$stmt->execute([$quest_id, $user_id]);

// Обновляем статус квеста в таблице quests
$stmt = $pdo->prepare("UPDATE quests SET status = 'in_progress' WHERE id = ?");
$stmt->execute([$quest_id]);

echo "Квест принят!";
header("Location: game.php"); // Перенаправление на страницу игры
exit;
