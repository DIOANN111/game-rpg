<?php
function checkInventoryOverload($user_id, $max_weight) {
    $current_weight = 0;

    // Получаем все предметы из инвентаря пользователя
    $query = "SELECT item_weight, item_count FROM inventory WHERE user_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);

    // Суммируем общий вес всех предметов
    while ($row = $stmt->fetch()) {
        $current_weight += $row['item_weight'] * $row['item_count'];
    }

    // Если текущий вес больше максимального, возвращаем true (перегрузка)
    return $current_weight > $max_weight;
}
?>
