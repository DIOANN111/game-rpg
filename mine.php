<?php
session_start();
require 'db.php'; // Подключение к базе данных

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    die('Пользователь не авторизован.');
}

$user_id = $_SESSION['user_id'];  // Получаем ID пользователя из сессии

// Функция для выполнения действия рудокопа
function mineOre($user_id) {
    global $pdo;

    // Получаем текущий уровень навыка рудокопа игрока
    $stmt = $pdo->prepare("SELECT mining_skill FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die('Пользователь не найден.');
    }

    // Увеличиваем навык на случайную величину (например, на 1-3)
    $new_mining_skill = $user['mining_skill'] + rand(1, 3);

    // Ограничиваем навык максимальным значением (например, 100)
    if ($new_mining_skill > 100) {
        $new_mining_skill = 100;
    }

    // Обновляем уровень навыка в базе данных
    $stmt = $pdo->prepare("UPDATE users SET mining_skill = ? WHERE id = ?");
    $stmt->execute([$new_mining_skill, $user_id]);

    // Добавляем руду в таблицу items, если её нет
    $stmt = $pdo->prepare("SELECT * FROM items WHERE name = 'Руда'");
    $stmt->execute();
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        // Если руды нет в таблице, добавляем её
        $stmt = $pdo->prepare("INSERT INTO items (name, description, type) VALUES ('Руда', 'Обычная руда', 'misc')");
        $stmt->execute();
        $item_id = $pdo->lastInsertId(); // Получаем id только что добавленного предмета
    } else {
        $item_id = $item['id']; // Если руда уже есть, используем её id
    }

// Теперь добавляем руду в инвентарь пользователя
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE user_id = ? AND item_id = ?");
    $stmt->execute([$user_id, $item_id]);
    $inventory_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($inventory_item) {
        // Если руда уже есть, увеличиваем количество
        $new_quantity = $inventory_item['quantity'] + rand(1, 3); // Увеличиваем количество на случайное число
        $stmt = $pdo->prepare("UPDATE inventory SET quantity = ? WHERE user_id = ? AND item_id = ?");
        $stmt->execute([$new_quantity, $user_id, $item_id]);
    } else {
        // Если руды нет в инвентаре, добавляем новый предмет с указанием item_id
        $stmt = $pdo->prepare("INSERT INTO inventory (user_id, item_name, quantity, item_id) VALUES (?, 'Руда', ?, ?)");
        $stmt->execute([$user_id, rand(1, 3), $item_id]);
    }


    return $new_mining_skill;
}

// Проверяем, был ли отправлен запрос на добычу руды
if (isset($_POST['mine_ore'])) {
    // Вызываем функцию добычи руды
    $new_skill = mineOre($user_id);

    // Перезагружаем страницу после выполнения действия
    header('Location: inventory.php');
    exit;
}

// Проверяем, был ли отправлен запрос на создание оружия
if (isset($_POST['craft_weapon'])) {
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE user_id = ? AND item_name = 'Руда'");
    $stmt->execute([$user_id]);
    $ore_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ore_item && $ore_item['quantity'] >= 5) {  // Например, 5 единиц руды для создания оружия
        // Уменьшаем количество руды в инвентаре
        $new_quantity = $ore_item['quantity'] - 5;
        $stmt = $pdo->prepare("UPDATE inventory SET quantity = ? WHERE user_id = ? AND item_name = 'Руда'");
        $stmt->execute([$new_quantity, $user_id]);

        // Создаем оружие
        $weapon_name = 'Меч из руды';
        $weapon_damage = 10;  // Например, урон от меча

        $stmt = $pdo->prepare("INSERT INTO items (name, description, type, damage, user_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$weapon_name, 'Меч из руды', 'weapon', $weapon_damage, $user_id]);

        // Добавляем оружие в инвентарь
        $item_id = $pdo->lastInsertId();
        $stmt = $pdo->prepare("INSERT INTO inventory (user_id, item_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $item_id]);

        echo "Вы создали оружие: " . $weapon_name;
    } else {
        echo "Недостаточно руды для создания оружия.";
    }
}

// Проверка на продажу руды
if (isset($_POST['sell_ore'])) {
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE user_id = ? AND item_name = 'Руда'");
    $stmt->execute([$user_id]);
    $ore_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ore_item && $ore_item['quantity'] > 0) {
        // Продажа руды за игровую валюту
        $quantity = $ore_item['quantity'];
        $gold_earned = $quantity * 10; // Например, за каждую единицу руды 10 золота

        // Обновляем количество руды
        $stmt = $pdo->prepare("UPDATE inventory SET quantity = 0 WHERE user_id = ? AND item_name = 'Руда'");
        $stmt->execute([$user_id]);

        // Добавляем золото пользователю
        $stmt = $pdo->prepare("UPDATE users SET gold = gold + ? WHERE id = ?");
        $stmt->execute([$gold_earned, $user_id]);

        echo "Вы продали руду и заработали " . $gold_earned . " золота.";
    } else {
        echo "У вас нет руды для продажи.";
    }
}

// Повышение навыка рудокопа с помощью руды
if (isset($_POST['level_up_mining'])) {
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE user_id = ? AND item_name = 'Руда'");
    $stmt->execute([$user_id]);
    $ore_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ore_item && $ore_item['quantity'] >= 3) {  // Для повышения навыка нужны 3 единицы руды
        // Уменьшаем количество руды в инвентаре
        $new_quantity = $ore_item['quantity'] - 3;
        $stmt = $pdo->prepare("UPDATE inventory SET quantity = ? WHERE user_id = ? AND item_name = 'Руда'");
        $stmt->execute([$new_quantity, $user_id]);

        // Повышаем уровень навыка
        $stmt = $pdo->prepare("UPDATE users SET mining_skill = mining_skill + 1 WHERE id = ?");
        $stmt->execute([$user_id]);

        echo "Ваш навык рудокопа повысился!";
    } else {
        echo "Недостаточно руды для повышения навыка.";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добыча руды</title>
    <style>
        .npc-button {
            background-color: #00ff00;
            color: #222;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
        }
        .npc-button:hover {
            background-color: #222;
            color: #00ff00;
        }
    </style>
</head>
<body>

<form method="POST">
    <button type="submit" name="mine_ore" class="npc-button">Добыть руду</button>
</form>

<form method="POST">
    <button type="submit" name="craft_weapon" class="npc-button">Создать оружие из руды</button>
</form>

<form method="POST">
    <button type="submit" name="sell_ore" class="npc-button">Продать руду</button>
</form>

<form method="POST">
    <button type="submit" name="level_up_mining" class="npc-button">Повысить навык рудокопа</button>
</form>

</body>
</html>
