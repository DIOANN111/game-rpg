<?php
require 'db.php';
session_start();

// Проверяем авторизацию
if (!isset($_SESSION['user_id'])) {
    die("Ошибка: Пользователь не авторизован.");
}

// Проверка, передан ли npc_id в GET-запросе
if (!isset($_GET['npc_id'])) {
    die("Ошибка: нет идентификатора NPC.");
}

$npc_id = (int)$_GET['npc_id'];

// Получаем предметы, доступные для продажи
$stmt = $pdo->prepare("SELECT * FROM inventory WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обрабатываем POST-запрос на продажу предмета
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id']) && isset($_POST['item_name'])) {
    $item_id = (int)$_POST['item_id'];
    $item_name = $_POST['item_name'];

    // Получаем информацию о предмете из таблицы inventory
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ? AND user_id = ?");
    $stmt->execute([$item_id, $_SESSION['user_id']]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        echo "Предмет не найден у вас.";
        exit;
    }

    // Получаем информацию о предмете из таблицы items
    $stmt = $pdo->prepare("SELECT id, name, price, type FROM items WHERE id = ?");
    $stmt->execute([$item['item_id']]);
    $item_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item_info) {
        echo "Ошибка: предмет не найден в таблице items.";
        exit;
    }

    // Проверка на наличие имени и цены у предмета
    if (empty($item_info['name']) || empty($item_info['price'])) {
        echo "Ошибка: у предмета нет имени или цены.";
        exit;
    }

    // Получаем информацию о монетах пользователя
    $stmt = $pdo->prepare("SELECT id, quantity FROM user_currency WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currency_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currency_item) {
        // Если монет нет, создаем запись
        $stmt = $pdo->prepare("INSERT INTO user_currency (user_id, currency_name, quantity) VALUES (?, ?, ?)");
        if (!$stmt->execute([$_SESSION['user_id'], 'Монеты', $item_info['price']])) {
            echo "Ошибка при добавлении монет.";
            exit;
        }
        $currency_item_id = $pdo->lastInsertId();
        $currency_item = ['id' => $currency_item_id, 'quantity' => $item_info['price']];
    } else {
        $currency_item_id = $currency_item['id'];
    }

    // Начинаем транзакцию
    try {
        $pdo->beginTransaction();

        // Увеличиваем количество монет
        $stmt = $pdo->prepare("UPDATE user_currency SET quantity = quantity + ? WHERE id = ?");
        if (!$stmt->execute([$item_info['price'], $currency_item_id])) {
            throw new Exception("Ошибка при обновлении количества монет.");
        }

        // Уменьшаем количество предмета или удаляем его
        if ($item['quantity'] > 1) {
            $stmt = $pdo->prepare("UPDATE inventory SET quantity = quantity - 1 WHERE id = ?");
            if (!$stmt->execute([$item['id']])) {
                throw new Exception("Ошибка при уменьшении количества предмета.");
            }
        } else {
            $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?");
            if (!$stmt->execute([$item['id']])) {
                throw new Exception("Ошибка при удалении предмета.");
            }
        }

        // Добавляем предмет в таблицу npc_items
        $stmt = $pdo->prepare("INSERT INTO npc_items (npc_id, item_id, item_name, price, type) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt->execute([$npc_id, $item_info['id'], $item_info['name'], $item_info['price'], $item_info['type']])) {
            throw new Exception("Ошибка при добавлении предмета торговца.");
        }

        // Подтверждаем транзакцию
        $pdo->commit();

        echo "Вы продали предмет: " . htmlspecialchars($item_info['name']) . " за " . $item_info['price'] . " монет.";

    } catch (Exception $e) {
        // Откатываем транзакцию в случае ошибки
        $pdo->rollBack();
        echo "Ошибка: " . $e->getMessage();
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/styles.css">
    <title>Продажа предмета</title>
    <style>

    </style>
</head>
<body>

<h1>Выберите предмет для продажи NPC</h1>

<form id="sell-form" method="POST">
    <ul>
        <?php foreach ($items as $item): ?>
            <li>
                <?= htmlspecialchars($item['item_name']) ?> - <?= $item['price'] ?> монет
                <button type="button" class="sell-button" data-item-id="<?= $item['id'] ?>" data-item-name="<?= htmlspecialchars($item['item_name']) ?>">
                    Продать
                </button>

            </li>
        <?php endforeach; ?>

    </ul>
    <a href="game.php" class="back">Назад к локации</a>
</form>

<div id="response-message"></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        $('.sell-button').on('click', function () {
            var itemId = $(this).data('item-id');
            var itemName = $(this).data('item-name');

            // Отправляем данные с помощью AJAX
            $.ajax({
                url: '',  // Текущая страница
                type: 'POST',
                data: {
                    item_id: itemId,
                    item_name: itemName
                },
                success: function (response) {
                    $('#response-message').html(response);  // Отображаем сообщение о продаже

                    // Удаляем проданный предмет из списка
                    $(`[data-item-id=${itemId}]`).closest('li').remove();
                },
                error: function () {
                    $('#response-message').html("Ошибка при продаже предмета.");
                }
            });
        });
    });
</script>

</body>
</html>
