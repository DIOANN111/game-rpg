<?php
session_start();
require 'db.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Получаем информацию о пользователе
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('Пользователь не найден.');
}



if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Получаем данные из формы
    $name = $_POST['name'];
    $type = $_POST['type'];
    $description = $_POST['description'];

    // Проверка, чтобы все поля были заполнены
    if (empty($name) || empty($type)) {
        $error = "Все поля обязательны для заполнения!";
    } else {
        // Вставка нового NPC в базу данных
        $stmt = $pdo->prepare("INSERT INTO npc (name, type, description) VALUES (?, ?, ?)");
        $stmt->execute([$name, $type, $description]);

        // Перенаправление на страницу с NPC
        header('Location: character.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создать NPC</title>
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

        .form-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 50px;
        }

        input, textarea {
            background: #3a3a3a;
            color: #fff;
            border: none;
            padding: 10px;
            margin: 10px 0;
            width: 250px;
            border-radius: 8px;
        }

        button {
            background: #3a3a3a;
            padding: 15px 30px;
            color: #fff;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #5c5c5c;
        }

        .error {
            color: red;
        }
    </style>
</head>
<body>

<h1>Создать NPC</h1>

<div class="form-container">
    <?php if (isset($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="name">Имя NPC:</label>
        <input type="text" id="name" name="name" required>

        <label for="type">Тип NPC:</label>
        <select id="type" name="type" required>
            <option value="npc">NPC</option>
            <option value="enemy">Враг</option>
            <option value="vendor">Продавец</option>
        </select>

        <label for="description">Описание NPC:</label>
        <textarea id="description" name="description" rows="4" cols="50"></textarea>

        <button type="submit">Создать NPC</button>
    </form>

    <a href="characters.php" style="margin-top: 20px; color: #fff;">Назад</a>
</div>

</body>
</html>
