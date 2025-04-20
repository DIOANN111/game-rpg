<?php
// Если форма была отправлена
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Получаем данные из формы
    $name = $_POST['name'];
    $race = $_POST['race'];
    $class = $_POST['class'];
    $gender = $_POST['gender'];
    $strength = $_POST['strength'];
    $agility = $_POST['agility'];
    $intelligence = $_POST['intelligence'];
    $health = $_POST['health'];
    $background = $_POST['background'];
    $x = $_POST['x'];
    $y = $_POST['y'];
    $z = $_POST['z'];

    // Подключение к базе данных
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=test', 'root', 'mysql');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Вставка данных персонажа в базу
        $stmt = $pdo->prepare("INSERT INTO characters (name, race, class, gender, strength, agility, intelligence, health, background, x, y, z) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $race, $class, $gender, $strength, $agility, $intelligence, $health, $background, $x, $y, $z]);

        // Перенаправление после успешного создания
        header("Location: characters.php"); // Перенаправляем на страницу со списком персонажей
        exit;
    } catch (PDOException $e) {
        echo 'Ошибка подключения к базе данных: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создать персонажа</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #1e1e1e;
            color: #e0e0e0;
            text-align: center;
            padding: 20px;
        }

        h1 {
            font-size: 32px;
            margin-bottom: 30px;
        }

        form {
            background: #2a2a2a;
            padding: 20px;
            border-radius: 10px;
            width: 400px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #fff;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #333;
            background-color: #1e1e1e;
            color: #fff;
        }

        .form-group input[type="submit"], .form-group input[type="reset"] {
            background-color: #3a3a3a;
            border: none;
            cursor: pointer;
        }

        .form-group input[type="submit"]:hover, .form-group input[type="reset"]:hover {
            background-color: #5c5c5c;
        }

        .back-link {
            margin-top: 20px;
            color: #fff;
            text-decoration: none;
            display: inline-block;
            padding: 10px 20px;
            background-color: #3a3a3a;
            border-radius: 5px;
        }

        .back-link:hover {
            background-color: #5c5c5c;
        }
    </style>
</head>
<body>

<h1>Создать персонажа</h1>

<form action="create_character.php" method="POST">
    <div class="form-group">
        <label for="name">Имя персонажа:</label>
        <input type="text" id="name" name="name" required>
    </div>

    <div class="form-group">
        <label for="race">Раса:</label>
        <select id="race" name="race">
            <option value="human">Человек</option>
            <option value="elf">Эльф</option>
            <option value="orc">Орк</option>
        </select>
    </div>

    <div class="form-group">
        <label for="class">Класс:</label>
        <select id="class" name="class">
            <option value="warrior">Воин</option>
            <option value="mage">Маг</option>
            <option value="rogue">Разбойник</option>
        </select>
    </div>

    <div class="form-group">
        <label>Пол:</label>
        <label for="male">Мужской</label>
        <input type="radio" id="male" name="gender" value="male" required>
        <label for="female">Женский</label>
        <input type="radio" id="female" name="gender" value="female">
    </div>

    <div class="form-group">
        <label for="strength">Сила:</label>
        <input type="number" id="strength" name="strength" min="1" max="10" required>
    </div>

    <div class="form-group">
        <label for="agility">Ловкость:</label>
        <input type="number" id="agility" name="agility" min="1" max="10" required>
    </div>

    <div class="form-group">
        <label for="intelligence">Интеллект:</label>
        <input type="number" id="intelligence" name="intelligence" min="1" max="10" required>
    </div>

    <div class="form-group">
        <label for="health">Здоровье:</label>
        <input type="number" id="health" name="health" min="1" max="100" required>
    </div>

    <div class="form-group">
        <label for="background">Фон персонажа:</label>
        <textarea id="background" name="background" rows="4" required></textarea>
    </div>

    <!-- Добавляем поля для координат -->
    <div class="form-group">
        <label for="x">Координата X:</label>
        <input type="number" id="x" name="x" value="0" required>
    </div>

    <div class="form-group">
        <label for="y">Координата Y:</label>
        <input type="number" id="y" name="y" value="0" required>
    </div>

    <div class="form-group">
        <label for="z">Координата Z:</label>
        <input type="number" id="z" name="z" value="0" required>
    </div>

    <div class="form-group">
        <input type="submit" value="Создать персонажа">
        <input type="reset" value="Сбросить">
    </div>
</form>

<a href="index.php" class="back-link">🔙 Назад</a>

</body>
</html>
