<?php
session_start();
require 'db.php'; // Подключение к базе данных

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Перенаправление на страницу логина, если не авторизован
    exit;
}

// Получаем информацию о текущем пользователе
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('Пользователь не найден.');
}

// Получаем информацию о выбранном игроке (пленнике)
$player_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;
if ($player_id) {
    $player_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $player_stmt->execute([$player_id]);
    $selected_player = $player_stmt->fetch(PDO::FETCH_ASSOC);

    if ($selected_player) {
        // Проверяем, находится ли игрок в рабстве
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'free' && $selected_player['status'] === 'slave') {
                // Освобождение игрока
                $update_stmt = $pdo->prepare("UPDATE users SET status = 'free', current_location = NULL WHERE id = ?");
                $update_stmt->execute([$selected_player['id']]);

                echo "<p>Вы освободили игрока " . htmlspecialchars($selected_player['username']) . ". Он теперь свободен.</p>";
            } elseif ($_POST['action'] === 'enslave' && $selected_player['status'] === 'free') {
                // Взятие в рабство
                $update_stmt = $pdo->prepare("UPDATE users SET status = 'slave', current_location = ? WHERE id = ?");
                $update_stmt->execute([$user['current_location'], $selected_player['id']]);

                echo "<p>Вы взяли игрока " . htmlspecialchars($selected_player['username']) . " в рабство.</p>";
            } else {
                echo "<p>Этот игрок уже находится в данном статусе.</p>";
            }
        }
    } else {
        echo "<p>Игрок не найден.</p>";
    }
} else {
    echo "<p>Неверный запрос.</p>";
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Управление статусом игрока</title>
</head>
<body>
<h1>Управление статусом игрока: <?= htmlspecialchars($selected_player['username']) ?></h1>

<!-- Форма для взятия в рабство или освобождения -->
<form method="post">
    <?php if ($selected_player['status'] === 'slave'): ?>
        <button type="submit" name="action" value="free">Освободить игрока</button>
    <?php elseif ($selected_player['status'] === 'free'): ?>
        <button type="submit" name="action" value="enslave">Взять в рабство</button>
    <?php endif; ?>
</form>

<a href="game.php">Вернуться в игру</a>
</body>
</html>
