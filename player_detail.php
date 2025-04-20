<?php
require 'db.php';
session_start();

// Получаем информацию о выбранном игроке
if (isset($_GET['player_id'])) {
    $player_id = (int) $_GET['player_id'];

    // Запрос для получения информации о пользователе
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$player_id]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);

    // Проверяем, существует ли такой игрок
    if (!$player) {
        // Если игрок не найден, перенаправляем на страницу игры
        header('Location: game.php');
        exit;
    }
} else {
    // Если ID игрока не передан, перенаправляем на страницу игры
    header('Location: game.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Информация о игроке</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #1e1e1e;
            color: #e0e0e0;
            text-align: center;
            margin: 0;
            padding: 0;
        }

        h1 {
            margin: 20px 0;
            font-size: 32px;
        }

        .player-info {
            background: #2a2a2a;
            padding: 20px;
            border-radius: 8px;
            width: 300px;
            margin: 20px auto;
            text-align: left;
        }

        .back {
            display: block;
            margin-top: 30px;
            color: #fff;
            text-decoration: none;
            background: #3a3a3a;
            padding: 10px 20px;
            margin: 10px;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            transition: 0.3s;
        }

        .back:hover {
            background: #5c5c5c;
        }
    </style>
</head>
<body>

<h1>Информация о игроке</h1>

<?php if ($player): ?>
    <div class="player-info">
        <p><strong>Имя пользователя:</strong> <?= htmlspecialchars($player['username']) ?></p>
        <p><strong>Дата регистрации:</strong> <?= htmlspecialchars($player['registration_date']) ?></p>
        <p><strong>Последняя активность:</strong> <?= htmlspecialchars($player['last_activity']) ?></p>
        <p><strong>Электронная почта:</strong> <?= htmlspecialchars($player['email']) ?></p>
        <!-- Добавьте другие поля, которые хотите отображать -->
    </div>
<?php endif; ?>

<!-- Кнопка "Назад" -->
<a href="game.php" class="back">Назад</a>

</body>
</html>
