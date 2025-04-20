<?php
session_start();
require 'db.php'; // Подключение к базе данных

// Проверка, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('Пользователь не найден.');
}

// Проверяем, в цепях ли текущий пользователь
if ($user['status'] === 'slave') {
    echo "<p>Вы в цепях.</p>";
    exit;
}

// Получаем информацию о выбранном игроке
$player_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;
$notification = '';

if ($player_id) {
    $player_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $player_stmt->execute([$player_id]);
    $selected_player = $player_stmt->fetch(PDO::FETCH_ASSOC);

    if ($selected_player) {

        // Обработка действия освобождения или взятия в рабство
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            if ($_POST['action'] === 'free' && $selected_player['status'] === 'slave') {
                // Освобождение игрока
                $update_stmt = $pdo->prepare("UPDATE users SET status = 'free', current_location = NULL WHERE id = ?");
                $update_stmt->execute([$selected_player['id']]);
                $selected_player['status'] = 'free'; // Обновляем статус в переменной
                $notification = "Вы освободили игрока " . htmlspecialchars($selected_player['username']) . ".";

                // Удаляем запись о рабстве из таблицы slavery
                $delete_stmt = $pdo->prepare("DELETE FROM slavery WHERE slave_id = ?");
                $delete_stmt->execute([$selected_player['id']]);
            } elseif ($_POST['action'] === 'enslave' && $selected_player['status'] === 'free') {
                // Взятие игрока в рабство
                $update_stmt = $pdo->prepare("UPDATE users SET status = 'slave', current_location = ? WHERE id = ?");
                $update_stmt->execute([$user['current_location'], $selected_player['id']]);
                $selected_player['status'] = 'slave'; // Обновляем статус в переменной
                $notification = "Вы взяли игрока " . htmlspecialchars($selected_player['username']) . " в рабство.";


                // Добавление записи в таблицу slavery
                $insert_stmt = $pdo->prepare("INSERT INTO slavery (master_id, slave_id) VALUES (?, ?)");
                $insert_stmt->execute([$user_id, $selected_player['id']]);
            } else {
                $notification = "Этот игрок уже находится в данном статусе.";
            }
        }

        // Обработка перевода монет
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transfer_coins'])) {
            $amount = isset($_POST['amount']) ? (int)$_POST['amount'] : 0;

            // Проверяем, что сумма больше нуля
            if ($amount <= 0) {
                echo "<p style='color: red;'>Ошибка: сумма должна быть больше нуля.</p>";
            } else {
                // Проверяем, есть ли у игрока достаточно монет
                $stmt = $pdo->prepare("SELECT quantity FROM user_currency WHERE user_id = ? AND currency_name = 'Монеты'");
                $stmt->execute([$user_id]);
                $user_currency = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user_currency && $user_currency['quantity'] >= $amount) {
                    // Уменьшаем количество монет у пользователя
                    $stmt = $pdo->prepare("UPDATE user_currency SET quantity = quantity - ? WHERE user_id = ? AND currency_name = 'Монеты'");
                    $stmt->execute([$amount, $user_id]);

                    // Добавляем монеты получателю
                    $stmt = $pdo->prepare("INSERT INTO user_currency (user_id, currency_name, quantity) 
                                           VALUES (?, 'Монеты', ?) 
                                           ON DUPLICATE KEY UPDATE quantity = quantity + ?");
                    $stmt->execute([$player_id, $amount, $amount]);

                    echo "<p style='color: green;'>Вы успешно перевели $amount монет игроку " . htmlspecialchars($selected_player['username']) . ".</p>";
                } else {
                    echo "<p style='color: red;'>Ошибка: У вас недостаточно монет для перевода.</p>";
                }
            }
        }

        echo "<h1>Информация о игроке: " . htmlspecialchars($selected_player['username']) . "</h1>";
        echo "<div class='character-details'>";
        echo "<p><strong>Имя пользователя:</strong> " . htmlspecialchars($selected_player['username']) . "</p>";
        echo "<p><strong>Последняя активность:</strong> " . htmlspecialchars($selected_player['last_activity']) . "</p>";
        echo "<p><strong>Боевая статистика:</strong> " . htmlspecialchars($selected_player['combat_skill']) . "</p>";
        echo "<p><strong>Социальная статистика:</strong> " . htmlspecialchars($selected_player['crafting_skill']) . "</p>";
        echo "</div>";

        if ($notification) {
            echo "<p class='notification'>$notification</p>";
        }

        echo "<a href='skills.php?id=" . htmlspecialchars($selected_player['id']) . "' class='menu-button'>Навыки</a><br>";
        echo "<a href='transfer.php?id=" . htmlspecialchars($selected_player['id']) . "' class='menu-button'>Передать</a><br>";

        if ($user_id !== $player_id) {
            echo "<a href='message.php?id=" . htmlspecialchars($selected_player['id']) . "' class='menu-button'>Написать сообщение</a><br>";

            // Форма для изменения статуса игрока
            echo "<form method='post'>";
            if ($selected_player['status'] === 'slave') {
                echo "<button type='submit' name='action' value='free' class='menu-button'>Освободить игрока</button>";
            } elseif ($selected_player['status'] === 'free') {
                echo "<button type='submit' name='action' value='enslave' class='menu-button'>Взять в рабство</button>";
            }
            echo "</form>";

            // Форма для перевода монет
            echo "<form method='POST'>";
            echo "<label for='amount'>Количество монет для перевода:</label><br>";
            echo "<input type='number' name='amount' id='amount' min='1' required><br>";
            echo "<button type='submit' name='transfer_coins' class='menu-button'>Перевести монеты</button>";
            echo "</form>";
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
<a href="game.php">Вернуться в игру</a>

<style>
    body {
        font-family: 'Courier New', monospace;
        background-color: #1a1a1a;
        color: #00ff00;
        margin: 0;
        padding: 0;
    }

    h1 {
        font-size: 32px;
        margin: 20px 0;
    }

    .character-details {
        background: #222;
        padding: 20px;
        margin: 20px;
        border-radius: 8px;
        color: #fff;
        border: 2px solid #00ff00;
        box-shadow: 0 0 15px rgba(0, 255, 0, 0.5);
    }

    .menu-button {
        display: inline-block;
        background: #3a3a3a;
        padding: 10px 20px;
        margin: 10px;
        color: #00ff00;
        text-decoration: none;
        border-radius: 8px;
        transition: 0.3s;
        border: 2px solid #00ff00;
    }

    .menu-button:hover {
        background: #00ff00;
        color: #222;
    }

    .notification {
        background-color: #333;
        padding: 10px;
        margin-top: 20px;
        color: #00ff00;
        border: 2px solid #00ff00;
        box-shadow: 0 0 10px rgba(0, 255, 0, 0.5);
    }
</style>
</body>
</html>
