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

// Получаем информацию о игроке, которому отправляется сообщение
$recipient_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;
if ($recipient_id) {
    $recipient_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $recipient_stmt->execute([$recipient_id]);
    $recipient = $recipient_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recipient) {
        die('Игрок не найден.');
    }
} else {
    die('Неверный запрос.');
}

// Отправка сообщения
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO message (sender_id, recipient_id, message, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $recipient_id, $message]);
        echo "<p>Сообщение отправлено!</p>";
    } else {
        echo "<p>Сообщение не может быть пустым.</p>";
    }
}
?>

<div class="pipboy-container">
    <div class="pipboy-header">
        <div class="pipboy-logo">Текстовая RPG</div>
        <div class="pipboy-time"><?php echo date('H:i'); ?></div>
    </div>

    <div class="pipboy-screen">
        <h1>Написать сообщение игроку: <?php echo htmlspecialchars($recipient['username']); ?></h1>

        <form method="POST">
            <textarea name="message" rows="5" cols="50" placeholder="Введите ваше сообщение..."></textarea><br>
            <button type="submit" class="game-button">Отправить</button>
        </form>

        <a href="game.php" class="game-button">В игру</a>
    </div>
</div>

<style>
    body {
        font-family: 'Courier New', monospace;
        background-color: #1a1a1a;
        color: #00ff00;
        margin: 0;
        padding: 0;
    }

    .pipboy-container {
        width: 100%;
        max-width: 1000px;
        margin: 0 auto;
        border: 2px solid #00ff00;
        border-radius: 10px;
        background-color: #222;
        box-shadow: 0 0 15px rgba(0, 255, 0, 0.5);
    }

    .pipboy-header {
        display: flex;
        justify-content: space-between;
        padding: 15px;
        background-color: #333;
        border-bottom: 2px solid #00ff00;
    }

    .pipboy-logo {
        font-size: 24px;
        font-weight: bold;
        color: #00ff00;
    }

    .pipboy-time {
        font-size: 18px;
    }

    .pipboy-screen {
        padding: 20px;
        text-align: center;
    }

    .pipboy-screen h1 {
        font-size: 32px;
        margin-bottom: 20px;
    }

    textarea {
        background: #3a3a3a;
        color: #fff;
        border: 1px solid #555;
        padding: 10px;
        width: 80%;
    }

    .game-button {
        display: inline-block;
        background-color: #00ff00;
        color: #222;
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: bold;
        margin-top: 10px;
    }

    .game-button:hover {
        background-color: #222;
        color: #00ff00;
    }
</style>
