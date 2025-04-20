<?php
require 'db.php';
session_start();
$user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0; // ID пользователя из сессии

// Лимиты инвентаря
$max_weight = 110;
$max_capacity = 200;

// Получаем текущее количество предметов и их суммарный вес
$stmt = $pdo->prepare("SELECT SUM(inventory.quantity) AS total_items, SUM(items.weight * inventory.quantity) AS total_weight
                        FROM items 
                        JOIN inventory ON items.id = inventory.item_id
                        WHERE inventory.user_id = ?");
$stmt->execute([$user_id]);
$inventory_stats = $stmt->fetch(PDO::FETCH_ASSOC);

$current_weight = (float) $inventory_stats['total_weight'];
$current_capacity = (int) $inventory_stats['total_items'];

// Получаем количество монет из таблицы user_currency
$stmt = $pdo->prepare("SELECT quantity FROM user_currency WHERE user_id = ?");
$stmt->execute([$user_id]);
$currency_in_inventory = $stmt->fetch(PDO::FETCH_ASSOC);
$user_money = $currency_in_inventory ? $currency_in_inventory['quantity'] : 0;

// Проверяем текущий вес инвентаря
if ($current_weight > $max_weight) {
    // Увеличиваем силу
    $stmt = $pdo->prepare("UPDATE users SET strength = strength + 1 WHERE id = ?");
    $stmt->execute([$user_id]);
}


// Получаем предметы из инвентаря
$stmt = $pdo->prepare("SELECT items.*, inventory.quantity, inventory.equipped FROM items 
                        JOIN inventory ON items.id = inventory.item_id
                        WHERE inventory.user_id = ?");
$stmt->execute([$user_id]);
$inventory_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем текущую локацию пользователя через location_id
$stmt = $pdo->prepare("SELECT location_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_location = $stmt->fetch(PDO::FETCH_ASSOC)['location_id'] ?? null;

// Проверяем, существует ли локация в таблице locations
$stmt = $pdo->prepare("SELECT COUNT(*) FROM locations WHERE id = ?");
$stmt->execute([$user_location]);
$location_exists = $stmt->fetchColumn();

if ($location_exists > 0) {
    // Обработка выбрасывания предмета
    if (isset($_POST['throw_item'])) {
        $item_id = (int) $_POST['item_id'];

        try {
            $stmt = $pdo->prepare("INSERT INTO location_items (location_id, item_id) VALUES (?, ?)");
            $stmt->execute([$user_location, $item_id]);

            $stmt = $pdo->prepare("UPDATE inventory SET quantity = GREATEST(quantity - 1, 0) WHERE user_id = ? AND item_id = ?");
            $stmt->execute([$user_id, $item_id]);

            $stmt = $pdo->prepare("DELETE FROM inventory WHERE user_id = ? AND item_id = ? AND quantity = 0");
            $stmt->execute([$user_id, $item_id]);

            header("Location: inventory.php");
            exit;
        } catch (PDOException $e) {
            echo "Ошибка при выбрасывании предмета: " . $e->getMessage();
        }
    }
} else {
    echo "Ошибка: локация не существует.";
}

if (isset($_POST['equip_weapon'])) {
    $item_id = (int) $_POST['item_id'];

    try {
        $stmt = $pdo->prepare("SELECT equipped FROM inventory WHERE user_id = ? AND item_id = ?");
        $stmt->execute([$user_id, $item_id]);
        $equipped = $stmt->fetchColumn();

        $stmt = $pdo->prepare("UPDATE inventory SET equipped = ? WHERE user_id = ? AND item_id = ?");
        $stmt->execute([$equipped == 1 ? 0 : 1, $user_id, $item_id]);

        header("Location: inventory.php");
        exit;
    } catch (PDOException $e) {
        echo "Ошибка при изменении состояния оружия: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/styles.css">
    <title>Инвентарь</title>
</head>
<body>

<div class="pipboy-container">
    <div class="pipboy-header">
        <div class="pipboy-logo">Инвентарь</div>
        <div class="pipboy-time"><?= date('H:i') ?></div>
    </div>

    <div class="pipboy-screen">
        <h1>Ваш инвентарь</h1>
        <p>Вес: <?= $current_weight ?> / <?= $max_weight ?></p>
        <p>Вместимость: <?= $current_capacity ?> / <?= $max_capacity ?></p>
        <p>Монеты: <?= $user_money ?></p>  <!-- Показ монет через user_currency -->

        <?php if (count($inventory_items) > 0): ?>
            <div class="items-list">
                <?php foreach ($inventory_items as $item): ?>
                    <div class="item">
                        <h3><?= htmlspecialchars($item['name']) ?></h3>
                        <p><?= htmlspecialchars($item['description']) ?></p>
                        <p>Тип: <?= htmlspecialchars($item['type']) ?></p>
                        <p>Количество: <?= $item['quantity'] ?></p>
                        <p>Вес: <?= htmlspecialchars($item['weight']) ?> кг</p>
                        <?php if ($item['type'] == 'weapon'): ?>
                            <form action="inventory.php" method="post">
                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                <button type="submit" name="equip_weapon" class="game-button">
                                    <?= $item['equipped'] == 1 ? 'Снять' : 'Надеть' ?>
                                </button>
                            </form>
                        <?php endif; ?>
                        <form action="inventory.php" method="post">
                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                            <button type="submit" name="throw_item" class="game-button">Выбросить</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Ваш инвентарь пуст.</p>
        <?php endif; ?>

        <a href="game.php" class="back">Назад</a>
    </div>
</div>

</body>
</html>
