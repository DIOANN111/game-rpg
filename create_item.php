<?php
// create_item.php

session_start();
require 'db.php';

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'] ?? null;
    $type = $_POST['type'];
    $price = $_POST['price'] ?? 0;  // Заменили cost на price
    $weight = $_POST['weight'] ?? 0.00;
    $damage = $_POST['damage'] ?? null;
    $defense = $_POST['defense'] ?? null;
    $health_restore = $_POST['health_restore'] ?? null;  // Добавляем восстановление здоровья для еды
    $location_id = $_POST['location_id'] ?? null;  // Локация для типа 'misc'

    if (empty($user_id)) {
        echo "Ошибка: пользователь не авторизован.";
        exit;
    }

    try {
        // Добавляем предмет в таблицу items
        if ($type == 'weapon') {
            $stmt = $pdo->prepare("INSERT INTO items (name, description, type, damage, price, weight, user_id) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $type, $damage, $price, $weight, $user_id]);
        } elseif ($type == 'armor') {
            $stmt = $pdo->prepare("INSERT INTO items (name, description, type, defense, price, weight, user_id) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $type, $defense, $price, $weight, $user_id]);
        } elseif ($type == 'food') {
            $stmt = $pdo->prepare("INSERT INTO items (name, description, type, health_restore, price, weight, user_id) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $type, $health_restore, $price, $weight, $user_id]);
        } elseif ($type == 'misc') {
            // Если тип предмета 'misc', то добавляем его на локацию
            $stmt = $pdo->prepare("INSERT INTO items (name, description, type, price, weight, user_id, location_id) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $type, $price, $weight, $user_id, $location_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO items (name, description, type, price, weight, user_id) 
                                   VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $type, $price, $weight, $user_id]);
        }

        // Получаем ID последнего вставленного предмета
        $item_id = $pdo->lastInsertId();

        // Добавляем предмет в инвентарь с дополнительными параметрами, если это не тип 'misc'
        if ($type !== 'misc') {
            $stmt = $pdo->prepare("INSERT INTO inventory (user_id, item_id, item_name, quantity, weapon, equipped, name, price, type) 
                                   VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $user_id,
                $item_id,
                $name,
                $type == 'weapon' ? 1 : 0,  // Если оружие, то weapon = 1
                0, // Для equipped ставим 0, так как это больше не используется
                $name,
                $price,  // Используем price вместо cost
                $type
            ]);
        }

        $_SESSION['message'] = "Предмет '$name' создан и добавлен!";  // Сообщение об успешном создании
        header('Location: items.php');
        exit;

    } catch (PDOException $e) {
        echo "Ошибка: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/edit_location_form.css">
</head>
<body>

<h1>Создать предмет</h1>

<div class="form-container">
    <form method="POST">
        <label for="name">Название предмета:</label>
        <input type="text" id="name" name="name" required>

        <label for="description">Описание (необязательно):</label>
        <input type="text" id="description" name="description">

        <label for="type">Тип предмета:</label>
        <select id="type" name="type" required>
            <option value="weapon">Оружие</option>
            <option value="armor">Броня</option>
            <option value="misc">Окружающая среда</option>
            <option value="ore">Руда</option>
            <option value="food">Еда</option>  <!-- Добавляем тип "еда" -->
        </select>

        <label for="price">Стоимость (необязательно):</label>
        <input type="number" id="price" name="price">

        <label for="weight">Вес (необязательно):</label>
        <input type="number" step="0.01" id="weight" name="weight">

        <div id="weapon_fields" style="display: none;">
            <label for="damage">Урон (необязательно):</label>
            <input type="number" id="damage" name="damage">
        </div>

        <div id="armor_fields" style="display: none;">
            <label for="defense">Защита (необязательно):</label>
            <input type="number" id="defense" name="defense">
        </div>

        <div id="food_fields" style="display: none;">
            <label for="health_restore">Восстановление здоровья (необязательно):</label>
            <input type="number" id="health_restore" name="health_restore">
        </div>

        <!-- Поле для выбора локации для типа 'misc' -->
        <div id="misc_fields" style="display: none;">
            <label for="location_id">Локация:</label>
            <select id="location_id" name="location_id" required>
                <!-- Предполагается, что список локаций уже существует в базе данных -->
                <?php
                // Получаем список локаций из базы данных
                $stmt = $pdo->query("SELECT id, name FROM locations");
                $locations = $stmt->fetchAll();
                foreach ($locations as $location) {
                    echo "<option value=\"{$location['id']}\">{$location['name']}</option>";
                }
                ?>
            </select>
        </div>

        <button type="submit">Создать предмет</button>
    </form>
</div>

<a class="back" href="items.php">Назад</a>

<script>
    // Обработка изменения типа предмета (оружие/броня/вещь/еда/окружающая среда)
    const typeSelect = document.getElementById('type');
    const weaponFields = document.getElementById('weapon_fields');
    const armorFields = document.getElementById('armor_fields');
    const foodFields = document.getElementById('food_fields');
    const miscFields = document.getElementById('misc_fields');

    typeSelect.addEventListener('change', function() {
        const type = typeSelect.value;

        if (type === 'weapon') {
            weaponFields.style.display = 'block';
            armorFields.style.display = 'none';
            foodFields.style.display = 'none';
            miscFields.style.display = 'none';
        } else if (type === 'armor') {
            armorFields.style.display = 'block';
            weaponFields.style.display = 'none';
            foodFields.style.display = 'none';
            miscFields.style.display = 'none';
        } else if (type === 'food') {
            foodFields.style.display = 'block';
            weaponFields.style.display = 'none';
            armorFields.style.display = 'none';
            miscFields.style.display = 'none';
        } else if (type === 'misc') {
            miscFields.style.display = 'block';
            weaponFields.style.display = 'none';
            armorFields.style.display = 'none';
            foodFields.style.display = 'none';
        } else {
            weaponFields.style.display = 'none';
            armorFields.style.display = 'none';
            foodFields.style.display = 'none';
            miscFields.style.display = 'none';
        }
    });

    // Инициализация скрытых полей в зависимости от выбранного типа
    typeSelect.dispatchEvent(new Event('change'));
</script>

</body>
</html>
