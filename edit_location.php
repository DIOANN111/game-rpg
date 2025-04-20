<?php
require 'db.php';

// Получаем список локаций
$stmt = $pdo->query("SELECT * FROM locations ORDER BY id ASC");
$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать локацию</title>
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

        .back {
            display: block;
            margin-top: 30px;
            background: none;
            border: none;
            color: #00ff00;
            font-size: 18px;
            text-decoration: none;
            cursor: pointer;
            text-align: center;
            padding: 10px;
            background-color: #444;
            border-radius: 8px;
            transition: background-color 0.3s;
        }

        .back:hover {
            background-color: #00ff00;
            color: #222;
        }

        .location-list {
            margin-top: 30px;
        }

        .location-list a {
            display: block;
            width: 250px;
            padding: 15px;
            margin: 10px;
            text-decoration: none;
            font-size: 18px;
            color: #fff;
            background: #3a3a3a;
            border-radius: 8px;
            transition: 0.3s;
        }

        .location-list a:hover {
            background: #5c5c5c;
        }
    </style>
</head>
<body>

<div class="pipboy-container">
    <div class="pipboy-header">
        <div class="pipboy-logo">Редактировать Локацию</div>
        <div class="pipboy-time">Текущее время: <?= date('H:i:s') ?></div>
    </div>

    <div class="pipboy-screen">
        <a class="back" href="locations.php">🔙 Назад</a>

        <h2>Выберите локацию:</h2>

        <div class="location-list">
            <?php if (!empty($locations)): ?>
                <?php foreach ($locations as $location): ?>
                    <a href="edit_location_form.php?id=<?= $location['id'] ?>">
                        <?= htmlspecialchars($location['name']) ?> (X: <?= $location['x'] ?>, Y: <?= $location['y'] ?>, Z: <?= $location['z'] ?>)
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Локаций пока нет.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
