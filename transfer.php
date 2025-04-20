<?php
session_start();
require 'db.php'; // Подключение к базе данных

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Перенаправление на страницу логина, если не авторизован
    exit;
}

$recipient_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;
if (!$recipient_id) {
    echo "<p>Неверный запрос.</p>";
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('Пользователь не найден.');
}

$stmt = $pdo->prepare("SELECT * FROM inventory WHERE user_id = ?");
$stmt->execute([$user_id]);
$inventory_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($inventory_items)) {
    echo "<p>Ваш инвентарь пуст.</p>";
    echo "<a href='game.php' class='menu-button'>Назад в меню</a>";
    exit;
}

echo "<h1>Выберите предмет для передачи игроку " . htmlspecialchars($recipient_id) . "</h1>";
echo "<form action='process_transfer.php' method='POST'>";
echo "<label for='item_id'>Выберите предмет:</label>";
echo "<select name='item_id' id='item_id'>";

foreach ($inventory_items as $item) {
    echo "<option value='" . htmlspecialchars($item['id']) . "'>" . htmlspecialchars($item['item_name']) . "</option>";
}

echo "</select>";
echo "<input type='hidden' name='recipient_id' value='" . htmlspecialchars($recipient_id) . "'>";
echo "<input type='submit' value='Передать предмет' class='menu-button'>";
echo "</form>";

echo "<a href='index.php' class='menu-button'>Назад в меню</a>";
?>

<style>
    body { font-family: Arial, sans-serif; background: #1e1e1e; color: #e0e0e0; text-align: center; margin: 0; padding: 0; }
    h1 { margin: 20px 0; font-size: 32px; }
    form { background: #3a3a3a; padding: 20px; margin: 20px; border-radius: 8px; color: #fff; }
    .menu-button { display: inline-block; background: #3a3a3a; padding: 10px 20px; margin: 10px; color: #fff; text-decoration: none; border-radius: 8px; transition: 0.3s; }
    .menu-button:hover { background: #5c5c5c; }
    select { padding: 10px; margin: 10px; background: #5c5c5c; color: white; border: none; border-radius: 8px; }
    input[type="submit"] { background: #4CAF50; }
</style>
