<?php
session_start();
require 'db.php'; // Подключение к базе данных

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if (!$userId) {
    die("Ошибка: Пользователь не авторизован.");
}

$message = "";

// Логика для извлечения предмета
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

// Получаем все предметы в банковской ячейке
$stmt = $pdo->prepare("SELECT i.id, i.name, bs.quantity 
                       FROM bank_storage bs 
                       JOIN items i ON bs.item_id = i.id
                       WHERE bs.user_id = ?");
$stmt->execute([$userId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/styles.css">
    <title>Извлечение предметов из банка</title>
</head>
<body>
<h1>Предметы в вашей банковской ячейке</h1>

<?php if ($message): ?>
    <p style="color: green; font-weight: bold;"> <?php echo $message; ?> </p>
<?php endif; ?>

<!-- Просмотр предметов, доступных для изъятия -->
<ul>
    <?php if (!empty($items)): ?>
        <?php foreach ($items as $item): ?>
            <li>
                <a href="items_detail.php?item_id=<?php echo (int)$item['id']; ?>">
                    <?php echo htmlspecialchars($item['name']); ?>
                </a>
                (<?php echo (int)$item['quantity']; ?> шт.)
                <!-- Форма для изъятия предмета -->
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="item_id" value="<?php echo (int)$item['id']; ?>">
                    <button type="submit" name="withdraw">Взять</button>
                </form>
            </li>
        <?php endforeach; ?>
    <?php else: ?>
        <p>У вас нет предметов в банковской ячейке.</p>
    <?php endif; ?>
</ul>

<br>
<a href="bank.php">Назад в банковскую ячейку</a>
<br>
<a href="game.php">Назад в игру</a>
</body>
</html>
