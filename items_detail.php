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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw'])) {
    $itemId = (int)$_POST['item_id'];

    // Получаем информацию о предмете в банке
    $stmt = $pdo->prepare("SELECT quantity FROM bank_storage WHERE user_id = ? AND item_id = ?");
    $stmt->execute([$userId, $itemId]);
    $bankItem = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bankItem) {
        $message = "Ошибка: Предмет не найден в банке.";
    } else {
        $quantity = (int)$bankItem['quantity'];

        // Удаляем предмет из банка
        $stmt = $pdo->prepare("DELETE FROM bank_storage WHERE user_id = ? AND item_id = ?");
        $stmt->execute([$userId, $itemId]);

        // Проверяем, есть ли уже этот предмет в инвентаре
        $stmt = $pdo->prepare("SELECT quantity FROM inventory WHERE user_id = ? AND item_id = ?");
        $stmt->execute([$userId, $itemId]);
        $inventoryItem = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($inventoryItem) {
            // Если предмет уже есть, увеличиваем его количество
            $newInventoryQuantity = $inventoryItem['quantity'] + $quantity;
            $stmt = $pdo->prepare("UPDATE inventory SET quantity = ? WHERE user_id = ? AND item_id = ?");
            $stmt->execute([$newInventoryQuantity, $userId, $itemId]);
        } else {
            // Если предмета в инвентаре нет, добавляем новую запись
            $stmt = $pdo->prepare("INSERT INTO inventory (user_id, item_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $itemId, $quantity]);
        }

        $message = "Предмет успешно извлечен и добавлен в инвентарь.";
    }
}
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
        <form method="POST" action="retrieve.php">
            <input type="hidden" name="item_id" value="<?= $item_id ?>">
            <button type="submit" name="withdraw" class="button">Забрать из банка</button>
        </form>
    </div>
<?php else: ?>
    <p style="color: red;">Предмет не найден.</p>
<?php endif; ?>
<a href="retrieve.php">назад</a><br>
<a href="game.php?location_id=<?= $location_id ?>" class="button">в игру</a>

</body>
</html>
