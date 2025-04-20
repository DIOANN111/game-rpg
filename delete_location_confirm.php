<?php
require 'db.php';

// Проверяем, есть ли ID локации для удаления
if (isset($_GET['id'])) {
    $location_id = (int)$_GET['id'];

    // Получаем информацию о локации для подтверждения
    $stmt = $pdo->prepare("SELECT * FROM locations WHERE id = :id");
    $stmt->execute(['id' => $location_id]);
    $location = $stmt->fetch(PDO::FETCH_ASSOC);

    // Если локация не найдена
    if (!$location) {
        echo "Локация не найдена.";
        exit;
    }

    // Обработка подтверждения удаления
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['confirm'])) {
            // Удаляем локацию из базы данных
            $stmt = $pdo->prepare("DELETE FROM locations WHERE id = :id");
            $stmt->execute(['id' => $location_id]);
            header("Location: locations.php");
            exit;
        } elseif (isset($_POST['cancel'])) {
            header("Location: locations.php");
            exit;
        }
    }
} else {
    echo "ID локации не указан.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подтверждение удаления</title>
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
            max-width: 600px;
            margin: 100px auto;
            border: 2px solid #00ff00;
            border-radius: 10px;
            background-color: #222;
            box-shadow: 0 0 15px rgba(0, 255, 0, 0.5);
            padding: 20px;
            text-align: center;
        }

        .pipboy-header {
            font-size: 24px;
            font-weight: bold;
            color: #00ff00;
            margin-bottom: 20px;
        }

        .message {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .buttons input {
            padding: 10px 20px;
            background: #00ff00;
            color: #222;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
        }

        .buttons input:hover {
            background: #222;
            color: #00ff00;
        }

        .buttons .cancel {
            background: #e74c3c;
        }

        .buttons .cancel:hover {
            background: #c0392b;
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
    </style>
</head>
<body>

<div class="pipboy-container">
    <div class="pipboy-header">Подтверждение удаления</div>

    <div class="message">
        Вы действительно хотите удалить локацию: <strong><?= htmlspecialchars($location['name']) ?></strong>?
    </div>

    <form method="POST">
        <div class="buttons">
            <input type="submit" name="confirm" value="Удалить">
            <input type="submit" name="cancel" value="Отменить" class="cancel">
        </div>
    </form>


</div>

</body>
</html>
