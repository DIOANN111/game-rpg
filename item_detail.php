<?php
require 'db.php';
session_start();

// Получаем ID предмета и ID пользователя
$item_id = isset($_GET['item_id']) ? (int) $_GET['item_id'] : 0;
$user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

// Проверяем, авторизован ли пользователь
if ($user_id <= 0) {
    die("Ошибка: Пользователь не авторизован.");
}

// Получаем текущую локацию пользователя
$stmt = $pdo->prepare("SELECT location_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_location = $stmt->fetch(PDO::FETCH_ASSOC);
$location_id = $current_location['location_id'] ?? 0;

// Отладка: Выводим информацию о текущем пользователе и локации
echo "user_id: $user_id, location_id: $location_id<br>";

if ($location_id <= 0) {
    echo "Локация не установлена. Устанавливаем локацию по умолчанию.<br>";

    // Получаем последнюю установленную локацию пользователя или ID 1 по умолчанию
    $stmt = $pdo->prepare("SELECT location_id FROM users WHERE id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $previous_location = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($previous_location && $previous_location['location_id'] > 0) {
        $location_id = $previous_location['location_id'];
        echo "Используем последнюю локацию пользователя: $location_id<br>";
    } else {
        // Устанавливаем локацию по умолчанию, например, ID = 1, если предыдущая локация не найдена
        $location_id = 1;  // Измените на правильное значение
        echo "Новая локация для пользователя: $location_id<br>";
    }

    // Обновляем локацию пользователя в базе данных
    $stmt = $pdo->prepare("UPDATE users SET location_id = ? WHERE id = ?");
    $stmt->execute([$location_id, $user_id]);
}

// Получаем данные предмета
$item = null;
if ($item_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Отладка: Выводим текущие значения
echo "location_id: $location_id, item_id: $item_id<br>";

if ($item) {
    // Проверяем, есть ли предмет в какой-либо локации (не только в текущей локации пользователя)
    $stmt = $pdo->prepare("SELECT * FROM location_items WHERE item_id = ?");
    $stmt->execute([$item_id]);
    $location_item = $stmt->fetch(PDO::FETCH_ASSOC);

    // Отладка: Выводим результат запроса
    if ($location_item) {
        echo "Предмет найден в локации: " . $location_item['location_id'] . "<br>";
    } else {
        echo "Предмет не найден в любых локациях!<br>";
    }

    // Обработка нажатия кнопки "Взять предмет"
    if (isset($_POST['take_item'])) {
        if ($location_item) {
            // Получаем имя предмета
            $item_name = $item['name'];
            $item_price = $item['price'];  // Получаем цену
            $item_type = $item['type'];    // Получаем тип
            $item_weapon = $item['weapon']; // Получаем информацию о оружии (если есть)
            $item_equipped = 0;  // По умолчанию предмет не экипирован
            $npc_id = null; // Если этот предмет связан с NPC, запишите его ID сюда

            // Вместо проверки на наличие предмета в инвентаре, просто пробуем добавить его с использованием ON DUPLICATE KEY UPDATE
            try {
                // Используем ON DUPLICATE KEY UPDATE для увеличения количества, если предмет уже есть в инвентаре
                $stmt = $pdo->prepare("INSERT INTO inventory (user_id, item_id, item_name, quantity, in_game, weapon, equipped, price, type, npc_id) 
                                       VALUES (?, ?, ?, 1, 0, ?, 0, ?, ?, ?) 
                                       ON DUPLICATE KEY UPDATE quantity = quantity + 1");
                $stmt->execute([
                    $user_id, $item_id, $item_name, $item_weapon, $item_price, $item_type, $npc_id
                ]);

                // Удаляем предмет из текущей локации, где он был найден
                $stmt = $pdo->prepare("DELETE FROM location_items WHERE item_id = ?");
                $stmt->execute([$item_id]);

                // Переадресация обратно в игру
                header("Location: game.php?location_id=$location_id");
                exit();
            } catch (Exception $e) {
                echo "<p style='color: red;'>Ошибка при добавлении предмета в инвентарь: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: red;'>Предмет отсутствует в любых локациях!</p>";
        }
    } else {
        echo "<p style='color: red;'>Предмет не найден.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/styles.css">
    <title>Описание предмета</title>
</head>
<body>

<?php if ($item): ?>
    <h1><?= htmlspecialchars($item['name']) ?></h1>
    <div class="item-details">
        <p><?= htmlspecialchars($item['description']) ?></p>
        <p>Тип: <?= htmlspecialchars($item['type']) ?></p>
        <?php if ($item['type'] == 'weapon'): ?>
            <p>Урон: <?= htmlspecialchars($item['damage']) ?></p>
        <?php elseif ($item['type'] == 'armor'): ?>
            <p>Защита: <?= htmlspecialchars($item['defense']) ?></p>
        <?php endif; ?>
        <form method="POST">
            <button type="submit" name="take_item" class="button">Взять</button>
        </form>
    </div>
<?php else: ?>
    <p style="color: red;">Предмет не найден.</p>
<?php endif; ?>

<a href="game.php?location_id=<?= $location_id ?>" class="button">Назад</a>

</body>
</html>
