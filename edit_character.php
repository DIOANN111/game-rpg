<?php
require 'db.php';

// Получаем ID персонажа из URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Получаем данные персонажа из базы
    $stmt = $pdo->prepare("SELECT * FROM characters WHERE id = ?");
    $stmt->execute([$id]);
    $character = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$character) {
        echo "<p>Персонаж не найден.</p>";
        exit;
    }
}

// Обработка формы, если она была отправлена
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

    // Обновляем данные персонажа в базе
    $stmt = $pdo->prepare("UPDATE characters SET name = ?, race = ?, class = ?, gender = ?, strength = ?, agility = ?, 
                          intelligence = ?, health = ?, background = ?, x = ?, y = ?, z = ? WHERE id = ?");
    $stmt->execute([$name, $race, $class, $gender, $strength, $agility, $intelligence, $health, $background, $x, $y, $z, $id]);

    // Выводим сообщение об успешном обновлении
    echo "<h2>Персонаж успешно обновлен!</h2>";
}

// Форма редактирования персонажа
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать персонажа</title>
    <style>
        /* Стили как для create_character.php */
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

<h1>Редактировать персонажа</h1>

<form action="edit_character.php?id=<?php echo $id; ?>" method="POST">
    <div class="form-group">
        <label for="name">Имя персонажа:</label>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($character['name']); ?>" required>
    </div>

    <div class="form-group">
        <label for="race">Раса:</label>
        <select id="race" name="race">
            <option value="human" <?php echo $character['race'] == 'human' ? 'selected' : ''; ?>>Человек</option>
            <option value="elf" <?php echo $character['race'] == 'elf' ? 'selected' : ''; ?>>Эльф</option>
            <option value="orc" <?php echo $character['race'] == 'orc' ? 'selected' : ''; ?>>Орк</option>
        </select>
    </div>

    <div class="form-group">
        <label for="class">Класс:</label>
        <select id="class" name="class">
            <option value="warrior" <?php echo $character['class'] == 'warrior' ? 'selected' : ''; ?>>Воин</option>
            <option value="mage" <?php echo $character['class'] == 'mage' ? 'selected' : ''; ?>>Маг</option>
            <option value="rogue" <?php echo $character['class'] == 'rogue' ? 'selected' : ''; ?>>Разбойник</option>
        </select>
    </div>

    <div class="form-group">
        <label>Пол:</label>
        <label for="male">Мужской</label>
        <input type="radio" id="male" name="gender" value="male" <?php echo $character['gender'] == 'male' ? 'checked' : ''; ?> required>
        <label for="female">Женский</label>
        <input type="radio" id="female" name="gender" value="female" <?php echo $character['gender'] == 'female' ? 'checked' : ''; ?>>
    </div>

    <div class="form-group">
        <label for="strength">Сила:</label>
        <input type="number" id="strength" name="strength" value="<?php echo $character['strength']; ?>" min="1" max="10" required>
    </div>

    <div class="form-group">
        <label for="agility">Ловкость:</label>
        <input type="number" id="agility" name="agility" value="<?php echo $character['agility']; ?>" min="1" max="10" required>
    </div>

    <div class="form-group">
        <label for="intelligence">Интеллект:</label>
        <input type="number" id="intelligence" name="intelligence" value="<?php echo $character['intelligence']; ?>" min="1" max="10" required>
    </div>

    <div class="form-group">
        <label for="health">Здоровье:</label>
        <input type="number" id="health" name="health" value="<?php echo $character['health']; ?>" min="1" max="100" required>
    </div>

    <div class="form-group">
        <label for="background">Фон персонажа:</label>
        <textarea id="background" name="background" rows="4" required><?php echo htmlspecialchars($character['background']); ?></textarea>
    </div>

    <!-- Поля для координат -->
    <div class="form-group">
        <label for="x">Координата X:</label>
        <input type="number" id="x" name="x" value="<?php echo $character['x']; ?>" required>
    </div>

    <div class="form-group">
        <label for="y">Координата Y:</label>
        <input type="number" id="y" name="y" value="<?php echo $character['y']; ?>" required>
    </div>

    <div class="form-group">
        <label for="z">Координата Z:</label>
        <input type="number" id="z" name="z" value="<?php echo $character['z']; ?>" required>
    </div>

    <div class="form-group">
        <input type="submit" value="Сохранить изменения">
        <input type="reset" value="Сбросить">
    </div>
</form>

<a href="index.php" class="back-link">🔙 Назад</a>

</body>
</html>
