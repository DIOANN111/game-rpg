<?php
session_start();
require 'db.php'; // Подключение к базе данных

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

if (!$user_id) {
    $message = "Ошибка: Пользователь не авторизован.";
} else {
    // Обрабатываем добавление предмета в банк
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
}

// Получаем предметы из инвентаря
$stmt = $pdo->prepare("SELECT items.id, items.name, inventory.quantity 
                        FROM items 
                        JOIN inventory ON items.id = inventory.item_id
                        WHERE inventory.user_id = ?");
$stmt->execute([$user_id]);
$inventory_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/styles.css">
    <title>Положить предмет в банковскую ячейку</title>
</head>
<body>
<h1>Ваши предметы в инвентаре</h1>

<?php if (isset($message)) : ?>
    <p><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<ul>
    <?php if (!empty($inventory_items)): ?>
        <?php foreach ($inventory_items as $item): ?>
            <li>
                <!-- Ссылка на страницу деталей предмета -->
                <strong>Название:</strong>
                <a href="items_detali1.php?item_id=<?php echo (int)$item['id']; ?>">
                    <?php echo htmlspecialchars($item['name']); ?>
                </a><br>

                <strong>Количество:</strong> <?php echo (int)$item['quantity']; ?><br>

                <form method="POST">
                    <input type="hidden" name="item_id" value="<?php echo (int)$item['id']; ?>">
                    <button type="submit" name="deposit">Положить в банк</button>
                </form>
            </li>
            <hr>
        <?php endforeach; ?>
    <?php else: ?>
        <p>У вас нет предметов в инвентаре.</p>
    <?php endif; ?>
</ul>


<br>
<a href="bank.php">Назад в банковскую ячейку</a>
<br>
<a href="game.php">Назад в игру</a>
</body>
</html>
