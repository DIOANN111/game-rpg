<?php
require 'db.php';

// Получаем список локаций
$stmt = $pdo->query("SELECT * FROM locations ORDER BY id DESC");
$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Локации</title>
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

        .game-cards {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .game-card {
            background-color: #333;
            border-radius: 10px;
            padding: 20px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            border: 2px solid #00ff00;
        }

        .game-image {
            width: 100%;
            height: auto;
            border: 2px solid #00ff00;
            border-radius: 10px;
        }

        .game-description {
            margin: 10px 0;
            font-size: 14px;
            color: #a0a0a0;
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

        .npc-list, .items-list {
            margin-top: 30px;
        }

        .npc-item, .item {
            background-color: #444;
            padding: 10px;
            margin: 5px;
            color: #fff;
            border-radius: 8px;
        }

        .npc-button, .direction-button, .chat-button, .back, .description-button {
            background: none;
            border: none;
            color: #00ff00;
            font-size: 18px;
            text-decoration: none;
            cursor: pointer;
            display: block;
            width: 90%;
            text-align: left;
            padding: 10px;
            background-color: #444;
            border-radius: 8px;
            margin: 5px 0;
            transition: background-color 0.3s;
        }

        .npc-button:hover, .direction-button:hover, .chat-button:hover, .back:hover, .description-button:hover {
            background-color: #00ff00;
            color: #222;
        }

        .back {
            display: block;
            margin-top: 30px;
        }

        .chat-button {
            margin-top: 30px;
        }

        .menu {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 30px;
            text-align: center;
        }

        .menu a {
            display: inline-block;
            width: auto;
            padding: 15px;
            margin: 10px;
            text-decoration: none;
            font-size: 18px;
            color: #fff;
            background: #3a3a3a;
            border-radius: 8px;
            transition: 0.3s;
        }

        .menu a:hover {
            background: #5c5c5c;
        }

        .location {
            width: 80%;
            margin: 20px auto;
            background: #2a2a2a;
            padding: 15px;
            border-radius: 8px;
            text-align: left;
        }

        .location a {
            text-decoration: none;
            color: #ffcc00;
        }

        h1 {
            margin: 20px 0;
            font-size: 32px;
        }

    </style>
</head>
<body>

<div class="pipboy-container">
    <div class="pipboy-header">
        <div class="pipboy-logo">Локации</div>
        <div class="pipboy-time">Время: <span id="time">00:00</span></div>
    </div>

    <div class="pipboy-screen">
        <h1>Список локаций:</h1>

        <div class="menu">
            <a href="add_location.php">➕ Добавить локацию</a>
            <a href="edit_location.php">✏️ Редактировать локацию</a>
            <a class="back" href="game.php">🔙 В игру</a>
            <a class="back" href="menu.php">🔙 В меню</a>
        </div>

        <?php if (!empty($locations)): ?>
            <?php foreach ($locations as $location): ?>
                <div class="location">
                    <strong><?= htmlspecialchars($location['name']) ?></strong><br>
                    (X: <?= $location['x'] ?>, Y: <?= $location['y'] ?>, Z: <?= $location['z'] ?>)
                    <p><?= htmlspecialchars($location['description']) ?></p>
                    <a href="delete_location_confirm.php?id=<?= $location['id'] ?>">Удалить</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Локаций пока нет.</p>
        <?php endif; ?>
    </div>

    <div class="pipboy-footer">
        <div class="footer-left">
            <a href="#">Обратная связь</a>
        </div>
        <div class="footer-right">
            <a href="#">Авторские права</a>
        </div>
    </div>
</div>

</body>
</html>
