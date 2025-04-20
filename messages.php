<?php
require 'db.php';
session_start();

// Проверяем, что пользователь авторизован
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Получаем список пользователей для выбора получателя сообщения с учётом непрочитанных сообщений
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.username, 
           (SELECT COUNT(*) FROM message m WHERE m.sender_id != ? AND m.recipient_id = u.id AND m.is_read = FALSE) AS unread_count
    FROM users u
    JOIN message m ON (m.sender_id = u.id OR m.recipient_id = u.id)
    WHERE (m.sender_id = ? OR m.recipient_id = ?)
    AND u.id != ?
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обработка отправки нового сообщения
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recipient_id'], $_POST['message'])) {
    $recipient_id = (int) $_POST['recipient_id'];
    $message = trim($_POST['message']);

    if (!empty($message)) {
        // Сохраняем сообщение в базе данных
        $stmt = $pdo->prepare("INSERT INTO message (sender_id, recipient_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $recipient_id, $message]);

        // Перенаправляем на страницу сообщений после отправки
        header('Location: messages.php');
        exit;
    } else {
        $error_message = "Сообщение не может быть пустым.";
    }
}

// Обработка удаления сообщения
if (isset($_GET['delete_message_id'])) {
    $message_id = (int) $_GET['delete_message_id'];

    // Удаляем сообщение
    $stmt = $pdo->prepare("DELETE FROM message WHERE id = ? AND (sender_id = ? OR recipient_id = ?)");
    $stmt->execute([$message_id, $_SESSION['user_id'], $_SESSION['user_id']]);

    header('Location: messages.php');
    exit;
}

// Обработка удаления всего диалога
if (isset($_GET['delete_dialog_id'])) {
    $dialog_id = (int) $_GET['delete_dialog_id'];

    // Удаляем все сообщения между текущим пользователем и выбранным, но не удаляем пользователя из списка диалогов
    $stmt = $pdo->prepare("DELETE FROM message WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?)");
    $stmt->execute([$_SESSION['user_id'], $dialog_id, $dialog_id, $_SESSION['user_id']]);

    header('Location: messages.php');
    exit;
}

// Проверяем, если выбран конкретный диалог
$dialog_id = isset($_GET['dialog_id']) ? (int) $_GET['dialog_id'] : null;

if ($dialog_id) {
    // Получаем диалог с выбранным пользователем
    $stmt = $pdo->prepare("SELECT m.id, m.message, m.created_at, u1.username AS sender, u2.username AS recipient
                           FROM message m
                           JOIN users u1 ON m.sender_id = u1.id
                           JOIN users u2 ON m.recipient_id = u2.id
                           WHERE (m.sender_id = ? AND m.recipient_id = ?) OR (m.sender_id = ? AND m.recipient_id = ?)
                           ORDER BY m.created_at DESC");
    $stmt->execute([$_SESSION['user_id'], $dialog_id, $dialog_id, $_SESSION['user_id']]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Получаем информацию о выбранном пользователе
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$dialog_id]);
    $selected_user = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    // Если диалог не выбран, показываем список диалогов
    $messages = [];
    $selected_user = null;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="img/icona.ico" rel="shortcut icon">
    <link rel="stylesheet" href="CSS/styles.css">
    <title>Сообщения</title>
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

        .dialog-button {
            display: block;
            padding: 10px;
            margin: 5px 0;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            width: 100%;
        }

        .unread {
            background-color: orange;
        }

        .read {
            background-color: green;
        }

        .delete-dialog-button {
            display: inline-block;
            padding: 10px;
            margin: 10px 0;
            background-color: red;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
            width: 100%;
            font-weight: bold;
        }

        .delete-dialog-button:hover {
            background-color: darkred;
        }

        .send-button {
            display: inline-block;
            padding: 10px;
            margin: 10px 0;
            background-color: blue;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
            width: 100%;
            font-weight: bold;
        }

        .send-button:hover {
            background-color: darkblue;
        }

        .messages-container {
            padding: 20px;
            text-align: center;
        }

        .messages-list {
            margin-top: 20px;
        }

        .error-message {
            color: red;
            font-weight: bold;
        }

        .dialogs-list {
            margin-top: 30px;
        }

        .footer-left a, .footer-right a {
            color: #00ff00;
            text-decoration: none;
        }

        .footer-left a:hover, .footer-right a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="pipboy-container">
    <?php require 'header.php'; ?>

    <div class="messages-container">
        <h1>Сообщения</h1>

        <?php if (isset($error_message)): ?>
            <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <?php if ($dialog_id && $selected_user): ?>
            <h2>Диалог с <?= htmlspecialchars($selected_user['username']) ?></h2>

            <div class="messages-list">
                <?php if (count($messages) > 0): ?>
                    <ul>
                        <?php foreach ($messages as $message): ?>
                            <li>
                                <strong><?= htmlspecialchars($message['sender']) ?> -> <?= htmlspecialchars($message['recipient']) ?>:</strong>
                                <p><?= htmlspecialchars($message['message']) ?></p>
                                <small><?= htmlspecialchars($message['created_at']) ?></small>
                                <?php if ($message['sender'] === $_SESSION['user_id']): ?>
                                    <a href="messages.php?delete_message_id=<?= $message['id'] ?>" class="delete-button">Удалить</a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>Сообщений нет.</p>
                <?php endif; ?>
            </div>

            <h2>Отправить сообщение</h2>
            <form action="messages.php" method="POST">
                <input type="hidden" name="recipient_id" value="<?= $dialog_id ?>">

                <label for="message">Сообщение:</label>
                <textarea name="message" id="message" rows="5" required></textarea>

                <button type="submit" class="send-button">Отправить</button>
            </form>

            <a href="messages.php?delete_dialog_id=<?= $dialog_id ?>" class="delete-dialog-button" onclick="return confirm('Вы уверены, что хотите удалить весь диалог?');">Удалить диалог</a>

        <?php else: ?>
            <div class="dialogs-list">
                <?php foreach ($users as $user): ?>
                    <div class="dialog">
                        <a href="messages.php?dialog_id=<?= $user['id'] ?>" class="dialog-button <?= $user['unread_count'] > 0 ? 'unread' : 'read' ?>">
                            <?= htmlspecialchars($user['username']) ?>
                            <?php if ($user['unread_count'] > 0): ?>
                                (Новые сообщения)
                            <?php endif; ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer class="pipboy-footer">
        <div class="footer-left">
            <a href="game.php" class="description-button">В игру</a>
            <a href="inventory.php" class="description-button">Инвентарь</a>
            <a href="menu.php" class="back">Меню</a>
            <a href="chat.php" class="chat-button">Общий чат</a>
        </div>
    </footer>

    <?php require 'footer.php'; ?>
</div>
</body>
</html>
