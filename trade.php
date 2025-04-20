<?php
require 'db.php';
session_start();

// Проверяем, передан ли ID NPC
if (!isset($_GET['npc_id'])) {
    die("Ошибка: нет идентификатора NPC. <a href='game.php'>Вернуться к локациям</a>");
}

$npc_id = (int) $_GET['npc_id'];

// Получаем информацию о NPC
$stmt = $pdo->prepare("SELECT * FROM npc WHERE id = ?");
$stmt->execute([$npc_id]);
$npc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$npc) {
    die("NPC не найден.");
}

// Получаем предметы торговца из таблицы npc_items
$stmt = $pdo->prepare("SELECT * FROM npc_items WHERE npc_id = ?");
$stmt->execute([$npc_id]);
$npc_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Логика покупки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'buy') {
    $npc_item_id = (int) $_POST['npc_item_id'];  // Получаем item_id предмета
    $item_name = $_POST['item_name'];  // Получаем item_name предмета

    // Выводим переменные для отладки
    var_dump($_POST); // Проверяем, что все передано

    // Получаем информацию о предмете NPC
    $stmt = $pdo->prepare("SELECT * FROM npc_items WHERE id = ? AND npc_id = ?");
    $stmt->execute([$npc_item_id, $npc_id]);
    $npc_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($npc_item) {
        // Проверяем, что у предмета есть имя, цена и item_id
        if (empty($npc_item['item_name']) || empty($npc_item['price']) || empty($npc_item['item_id'])) {
            die("Ошибка: у предмета торговца нет имени, цены или item_id.");
        }

        // Проверка наличия item_id в таблице items
        $stmt = $pdo->prepare("SELECT id FROM items WHERE id = ?");
        $stmt->execute([$npc_item['item_id']]);
        $item_check = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item_check) {
            die("Ошибка: предмет с таким item_id не существует в таблице items.");
        }

        // Получаем количество монет из таблицы user_currency
        $stmt = $pdo->prepare("SELECT quantity FROM user_currency WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $currency_in_inventory = $stmt->fetch(PDO::FETCH_ASSOC);
        $user_money = $currency_in_inventory ? $currency_in_inventory['quantity'] : 0;

        // Проверка, что у пользователя достаточно денег
        if ($npc_item['price'] <= $user_money) {
            // Переносим предмет в инвентарь пользователя, добавляем item_id и item_name
            $stmt = $pdo->prepare("INSERT INTO inventory (user_id, item_id, item_name, price, type, quantity) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $npc_item['item_id'], $npc_item['item_name'], $npc_item['price'], $npc_item['type'], 1]);

            // Уменьшаем количество монет в таблице user_currency
            $stmt = $pdo->prepare("UPDATE user_currency SET quantity = quantity - ? WHERE user_id = ?");
            $stmt->execute([$npc_item['price'], $_SESSION['user_id']]);

            // Удаляем предмет из инвентаря торговца
            $stmt = $pdo->prepare("DELETE FROM npc_items WHERE id = ? AND npc_id = ?");
            $stmt->execute([$npc_item_id, $npc_id]);

            echo "Вы купили предмет: " . htmlspecialchars($npc_item['item_name']) . " за " . $npc_item['price'] . " монет.<br>";
            echo "<script>window.location.reload();</script>";

            // Перезагружаем страницу или выводим ссылку для обновления инвентаря
            echo "<a href='inventory.php'>Посмотреть ваш инвентарь</a><br>";
        } else {
            echo "У вас недостаточно денег для покупки этого предмета.<br>";
        }
    } else {
        echo "Предмет не найден у торговца.<br>";
    }
}
echo "NPC ID: " . htmlspecialchars($npc_id);  // Проверка на правильность npc_id
if (empty($npc_items)) {
    echo "Нет предметов для продажи у этого NPC.";
} else {
    var_dump($npc_items);  // Для отладки
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/styles.css">
    <title>Торговля с NPC - <?= htmlspecialchars($npc['name']) ?></title>
</head>
<body>

<div class="pipboy-container">

    <header class="pipboy-header">
        <div class="pipboy-logo">Моя Игра</div>
        <div class="pipboy-time"><?= date('H:i') ?></div>
    </header>

    <div class="pipboy-screen">
        <h1>Торговля с NPC: <?= htmlspecialchars($npc['name']) ?></h1>

        <div class="game-cards">
            <div class="game-card">
                <h2>Предметы торговца</h2>
                <ul>
                    <?php foreach ($npc_items as $npc_item): ?>
                        <li>
                            <?= htmlspecialchars($npc_item['item_name']) ?> - <?= $npc_item['price'] ?> монет? id предмета <?= $npc_item['item_id']?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="buy">
                                <input type="hidden" name="npc_item_id" value="<?= $npc_item['id'] ?>">
                                <input type="hidden" name="item_name" value="<?= htmlspecialchars($npc_item['item_name']) ?>">
                                <input type="submit" value="Купить" class="game-button">
                            </form>
                        </li>
                    <?php endforeach; ?>

                </ul>
            </div>
        </div>

        <a href="game.php?location_id=<?= $npc['location_id'] ?>" class="back">Назад к локации</a>
    </div>

</div>

</body>
</html>
