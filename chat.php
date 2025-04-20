<?php
require 'db.php';
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Перенаправление на страницу логина, если не авторизован
    exit;
}

// Получаем сообщения из базы данных
$stmt = $pdo->query("SELECT messages.*, users.username FROM messages 
                     JOIN users ON messages.user_id = users.id 
                     ORDER BY messages.timestamp DESC LIMIT 20");
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обработка отправки сообщения
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message']);
    if ($message !== '') {
        $stmt = $pdo->prepare("INSERT INTO messages (user_id, message) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $message]);
        header('Location: chat.php'); // Перенаправление для обновления страницы
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Общий чат</title>
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

        .pipboy-nav {
            background-color: #333;
            padding: 10px;
            text-align: center;
        }

        .nav-button {
            margin: 0 10px;
            text-decoration: none;
            color: #00ff00;
            font-size: 16px;
            font-weight: bold;
        }

        .nav-button:hover {
            color: #222;
            background-color: #00ff00;
            padding: 5px 10px;
            border-radius: 5px;
        }

        .pipboy-screen {
            padding: 20px;
            text-align: center;
        }

        .pipboy-screen h1 {
            font-size: 32px;
            margin-bottom: 20px;
        }

        .chat-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #333;
            border-radius: 8px;
            margin-top: 50px;
            height: 500px;
            overflow-y: scroll;
        }

        .message {
            background: #444;
            padding: 10px;
            margin: 10px 0;
            border-radius: 8px;
            text-align: left;
        }

        .message .username {
            font-weight: bold;
            color: #00ff00;
        }

        .message .timestamp {
            font-size: 12px;
            color: #aaa;
            margin-top: 5px;
        }

        .message .content {
            margin-top: 5px;
        }

        .input-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .message-input {
            width: 50%; /* уменьшена ширина поля ввода */
            padding: 10px;
            font-size: 16px;
            border-radius: 8px;
            border: none;
        }

        .send-button {
            padding: 10px 20px;
            margin-left: 10px;
            background: #3a3a3a;
            color: #00ff00;
            border-radius: 8px;
            cursor: pointer;
            border: none;
        }

        .send-button:hover {
            background: #5c5c5c;
            color: #222;
        }

        .game-button {
            margin-top: 30px;
            padding: 10px 20px;
            background: #3a3a3a;
            color: #00ff00;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            transition: 0.3s;
        }

        .game-button:hover {
            background: #5c5c5c;
            color: #222;
        }
    </style>
</head>
<body>

<div class="pipboy-container">
    <div class="pipboy-header">
        <div class="pipboy-logo">Общий чат</div>
        <div class="pipboy-time"><?= date('H:i') ?></div>
    </div>
    <div class="input-container">
        <form method="POST">
            <input type="text" name="message" class="message-input" placeholder="Введите сообщение..." required>
            <button type="submit" class="send-button">Отправить</button>
        </form>
    </div>

    <div class="pipboy-screen">
        <div class="chat-container">
            <!-- Отображение сообщений -->
            <?php foreach ($messages as $message): ?>
                <div class="message">
                    <div class="username"><?= htmlspecialchars($message['username']) ?></div>
                    <div class="content"><?= htmlspecialchars($message['message']) ?></div>
                    <div class="timestamp"><?= $message['timestamp'] ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Форма отправки сообщения -->


        <!-- Кнопка "В игру" -->
        <a href="game.php" class="game-button">В игру</a>
    </div>
</div>

</body>
</html>
