<?php
session_start();
require 'db.php'; // Подключение к базе данных

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Перенаправление на страницу логина, если не авторизован
    exit;
}

$item_id = isset($_POST['item_id']) && is_numeric($_POST['item_id']) ? (int)$_POST['item_id'] : null;
$recipient_id = isset($_POST['recipient_id']) && is_numeric($_POST['recipient_id']) ? (int)$_POST['recipient_id'] : null;

if (!$item_id || !$recipient_id) {
    echo "<p>Неверный запрос.</p>";
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ? AND user_id = ?");
$stmt->execute([$item_id, $user_id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    echo "<p>Предмет не найден в вашем инвентаре.</p>";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$recipient_id]);
$recipient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recipient) {
    echo "<p>Получатель не найден.</p>";
    exit;
}

// Переносим предмет в инвентарь получателя
$stmt = $pdo->prepare("UPDATE inventory SET user_id = ? WHERE id = ?");
$stmt->execute([$recipient_id, $item_id]);

echo "<p>Вы успешно передали предмет " . htmlspecialchars($item['item_name']) . " игроку " . htmlspecialchars($recipient['username']) . ".</p>";
echo "<a href='index.php' class='menu-button'>Назад в меню</a>";
?>

<style>
    body { font-family: Arial, sans-serif; background: #1e1e1e; color: #e0e0e0; text-align: center; margin: 0; padding: 0; }
    h1 { margin: 20px 0; font-size: 32px; }
    .menu-button { display: inline-block; background: #3a3a3a; padding: 10px 20px; margin: 10px; color: #fff; text-decoration: none; border-radius: 8px; transition: 0.3s; }
    .menu-button:hover { background: #5c5c5c; }
</style>
