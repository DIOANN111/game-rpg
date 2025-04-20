<?php
// quests_info.php

session_start();
require 'db.php';

// Проверка, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Перенаправление на страницу логина, если не авторизован
    exit;
}

// Получаем ID пользователя
$user_id = $_SESSION['user_id'];

// Запрос к базе данных, чтобы получить ID текущего квеста пользователя
$query = "SELECT current_quest_id FROM users WHERE id = :user_id";
$stmt = $pdo->prepare($query);
$stmt->execute(['user_id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Проверка, есть ли у пользователя текущий квест
if ($user && $user['current_quest_id']) {
    // Получаем информацию о текущем квесте
    $quest_id = $user['current_quest_id'];

    // Запрос к базе данных, чтобы получить данные о квесте
    $query = "SELECT * FROM quests WHERE id = :quest_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['quest_id' => $quest_id]);
    $quest = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $quest = null;
}

// Обработка отказа от квеста
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_quest'])) {
    // Обновляем таблицу users, удаляя текущий квест
    $query = "UPDATE users SET current_quest_id = NULL WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $user_id]);

    // Обновляем статус квеста на "not_started" (или любой другой статус, отражающий отмену)
    $query = "UPDATE quests SET status = 'not_started' WHERE id = :quest_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['quest_id' => $quest_id]);

    // Перенаправляем пользователя на страницу с квестами
    header('Location: quests_info.php');
    exit;
}

// Обработка принятия квеста
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_quest'])) {
    $quest_id_to_accept = $_POST['quest_id'];

    // Обновляем текущий квест пользователя и его статус
    $query = "UPDATE users SET current_quest_id = :quest_id WHERE id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['quest_id' => $quest_id_to_accept, 'user_id' => $user_id]);

    // Обновляем статус квеста на "in_progress"
    $query = "UPDATE quests SET status = 'in_progress' WHERE id = :quest_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['quest_id' => $quest_id_to_accept]);

    // Перенаправляем пользователя на страницу с квестами
    header('Location: quests_info.php');
    exit;
}

// Получаем все доступные квесты
$query = "SELECT * FROM quests";
$stmt = $pdo->prepare($query);
$stmt->execute();
$quests = $stmt->fetchAll();

// Функция для преобразования статуса квеста в русский язык
function translateStatus($status) {
    switch ($status) {
        case 'not_started':
            return 'Не принят';
        case 'in_progress':
            return 'В процессе';
        case 'completed':
            return 'Завершен';
        default:
            return 'Неизвестный статус';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои квесты</title>
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

        .quest-list {
            margin-top: 20px;
            text-align: left;
        }

        .quest-item {
            background: #333;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
        }

        .quest-item h3 {
            margin: 0;
            font-size: 24px;
        }

        .quest-item p {
            margin: 5px 0;
            font-size: 18px;
        }

        .pipboy-footer {
            background-color: #333;
            padding: 15px;
            border-top: 2px solid #00ff00;
            display: flex;
            justify-content: space-between;
            font-size: 14px;
        }

        .footer-left {
            color: #a0a0a0;
        }

        .footer-right {
            text-align: right;
        }

        .footer-left a, .footer-right a {
            color: #00ff00;
            text-decoration: none;
        }

        .footer-left a:hover, .footer-right a:hover {
            text-decoration: underline;
        }

        .cancel-button, .accept-button {
            background-color: #ff0000;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .cancel-button:hover, .accept-button:hover {
            background-color: #cc0000;
        }
    </style>
</head>

<body>

<div class="pipboy-container">
    <div class="pipboy-header">
        <div class="pipboy-logo">Мои квесты</div>
        <div class="pipboy-time"><?= date('H:i:s'); ?></div>
    </div>

    <div class="pipboy-screen">
        <h1>Текущие квесты</h1>

        <?php if ($quest): ?>
            <div class="quest-list">
                <div class="quest-item">
                    <h3><?= htmlspecialchars($quest['name']); ?></h3>
                    <p><strong>Описание:</strong> <?= htmlspecialchars($quest['description']); ?></p>
                    <p><strong>Статус:</strong> <?= translateStatus($quest['status']); ?></p>
                </div>
                <!-- Форма для отказа от квеста -->
                <form action="quests_info.php" method="POST">
                    <button type="submit" name="cancel_quest" class="cancel-button">Отказаться от квеста</button>
                </form>
            </div>
        <?php else: ?>
            <p>У вас нет текущих квестов.</p>
        <?php endif; ?>

        <h1>Доступные квесты</h1>

        <?php foreach ($quests as $quest): ?>
            <div class="quest-item">
                <h3><?= htmlspecialchars($quest['name']); ?></h3>
                <p><strong>Описание:</strong> <?= htmlspecialchars($quest['description']); ?></p>
                <p><strong>Статус:</strong> <?= translateStatus($quest['status']); ?></p>
                <form action="quests_info.php" method="POST">
                    <input type="hidden" name="quest_id" value="<?= $quest['id']; ?>">
                    <button type="submit" name="accept_quest" class="accept-button">Принять</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="pipboy-footer">
        <div class="footer-left">
            <a href="index.php">Главная</a>
        </div>
        <div class="footer-right">
            <a href="logout.php">Выход</a>
        </div>
    </div>
</div>

</body>
</html>
