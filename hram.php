<?php
require 'db.php';
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    die("Ошибка: Вы не авторизованы.");
}

$user_id = $_SESSION['user_id'];

// Получаем информацию о пользователе
$stmt = $pdo->prepare("SELECT max_health FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) die("Ошибка: Игрок не найден.");

// Восстанавливаем здоровье до максимума
$new_health = $user['max_health'];
$stmt = $pdo->prepare("UPDATE users SET health = ? WHERE id = ?");
$stmt->execute([$new_health, $user_id]);

echo "Ваше здоровье полностью восстановлено.<br>";
echo "<a href='game.php'>Вернуться в игру</a>";
?>
