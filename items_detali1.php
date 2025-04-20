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

if ($location_id <= 0) {
    echo "Локация не установлена. Устанавливаем локацию по умолчанию.<br>";

    $stmt = $pdo->prepare("SELECT location_id FROM users WHERE id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $previous_location = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($previous_location && $previous_location['location_id'] > 0) {
        $location_id = $previous_location['location_id'];
    } else {
        $location_id = 1;  // Устанавливаем локацию по умолчанию
    }

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

// Обработка нажатия кнопки "Положить в банк"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deposit']) && isset($_POST['item_id'])) {
    $item_id = (int)$_POST['item_id'];

    // Проверяем, есть ли предмет в инвентаре
    $stmt = $pdo->prepare("SELECT quantity FROM inventory WHERE user_id = ? AND item_id = ?");
    $stmt->execute([$user_id, $item_id]);
    $inventory_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$inventory_item) {
        $message = "Ошибка: Предмет не найден в инвентаре.";
    } else {
        $quantity = (int)$inventory_item['quantity'];

        // Удаляем предмет из инвентаря
        $stmt = $pdo->prepare("DELETE FROM inventory WHERE user_id = ? AND item_id = ?");
        $stmt->execute([$user_id, $item_id]);

        // Проверяем, есть ли уже такой предмет в банке
        $stmt = $pdo->prepare("SELECT quantity FROM bank_storage WHERE user_id = ? AND item_id = ?");
        $stmt->execute([$user_id, $item_id]);
        $bank_item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($bank_item) {
            // Если предмет уже есть в банке, увеличиваем его количество
            $new_bank_quantity = $bank_item['quantity'] + $quantity;
            $stmt = $pdo->prepare("UPDATE bank_storage SET quantity = ? WHERE user_id = ? AND item_id = ?");
            $stmt->execute([$new_bank_quantity, $user_id, $item_id]);
        } else {
            // Если предмета в банке нет, создаём новую запись
            $stmt = $pdo->prepare("INSERT INTO bank_storage (user_id, item_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $item_id, $quantity]);
        }

        $message = "Предмет успешно перемещён в банк.";
    }
}

// Проверка нажатия кнопки "Положить в банк"
if (isset($_POST['put_in_bank'])) {
    if ($item) {
        // Проверяем, есть ли предмет в инвентаре
        $stmt = $pdo->prepare("SELECT * FROM inventory WHERE user_id = ? AND item_id = ?");
        $stmt->execute([$user_id, $item_id]);
        $inventory_item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($inventory_item && $inventory_item['quantity'] > 0) {
            // Добавляем предмет в банк
            $stmt = $pdo->prepare("INSERT INTO bank_storage (user_id, item_id, quantity) 
                                   VALUES (?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE quantity = quantity + ?");
            $stmt->execute([$user_id, $item_id, 1, 1]);

            // Уменьшаем количество предметов в инвентаре
            $stmt = $pdo->prepare("UPDATE inventory SET quantity = quantity - 1 WHERE user_id = ? AND item_id = ?");
            $stmt->execute([$user_id, $item_id]);

            // Переадресация обратно в игру
            header("Location: game.php?location_id=$location_id");
            exit();
        } else {
            echo "<p style='color: red;'>Предмет отсутствует в вашем инвентаре!</p>";
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
        <form method="POST" action="store.php">
            <input type="hidden" name="item_id" value="<?= $item_id ?>">
            <button type="submit" name="deposit" class="button">Положить в банк</button>
        </form>
    </div>
<?php else: ?>
    <p style="color: red;">Предмет не найден.</p>
<?php endif; ?>

<a href="game.php?location_id=<?= $location_id ?>" class="button">Назад</a>

</body>
</html>
