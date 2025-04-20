<?php
// predmets.php

// Функция для добавления предметов в инвентарь
function addItemsToInventory($user_id, $items, $pdo)
{
    // Проверяем, что массив предметов не пуст
    if (empty($items)) {
        echo "Массив предметов пуст!<br>";
        return;
    }

    foreach ($items as $item) {
        // Убедитесь, что item['id'] и item['item_count'] существуют и являются числами
        if (!isset($item['id']) || !isset($item['item_count'])) {
            echo "Не найдены необходимые поля в массиве предмета.<br>";
            continue; // Пропускаем этот предмет
        }

        $item['id'] = (int) $item['id']; // Приводим id к целому числу
        $item['item_count'] = (int) $item['item_count']; // Приводим count к целому числу

        if ($item['item_count'] <= 0) {
            echo "Невозможно добавить предмет с количеством меньше или равным нулю: " . $item['id'] . "<br>";
            continue; // Пропускаем этот предмет, если его количество меньше или равно нулю
        }

        // Проверяем, есть ли уже предмет в инвентаре пользователя
        $stmt = $pdo->prepare("SELECT * FROM inventory WHERE user_id = ? AND item_id = ?");
        $stmt->execute([$user_id, $item['id']]);
        $inventory_item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($inventory_item) {
            // Если предмет уже есть в инвентаре, увеличиваем его количество
            $new_quantity = $inventory_item['quantity'] + $item['item_count'];

            // Логируем запрос
            echo "Обновление предмета (ID: " . $item['id'] . ") с новым количеством: " . $new_quantity . "<br>";

            $stmt = $pdo->prepare("UPDATE inventory SET quantity = ? WHERE user_id = ? AND item_id = ?");
            if ($stmt->execute([$new_quantity, $user_id, $item['id']])) {
                echo "Количество предмета обновлено успешно.<br>";
            } else {
                echo "Ошибка при обновлении количества предмета: " . implode(" ", $stmt->errorInfo()) . "<br>";
            }
        } else {
            // Если предмета нет в инвентаре, добавляем его с количеством
            // Логируем запрос
            echo "Добавление нового предмета (ID: " . $item['id'] . ") с количеством: " . $item['item_count'] . "<br>";

            $stmt = $pdo->prepare("INSERT INTO inventory (user_id, item_id, quantity) VALUES (?, ?, ?)");
            if ($stmt->execute([$user_id, $item['id'], $item['item_count']])) {
                echo "Предмет добавлен в инвентарь успешно.<br>";
            } else {
                echo "Ошибка при добавлении предмета в инвентарь: " . implode(" ", $stmt->errorInfo()) . "<br>";
            }
        }
    }
}
?>
