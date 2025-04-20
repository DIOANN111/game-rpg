<?php
// Подключаем файл для работы с базой данных
require 'db.php';
session_start();

// Массив для уведомлений о перемещении NPC
$npc_move_notifications = [];

// Получаем NPC в текущей локации
$stmt = $pdo->prepare("SELECT * FROM npc WHERE location_id = ?");
$stmt->execute([$_GET['location_id']]);  // Передайте id локации, чтобы получить NPC в этой локации
$npc = $stmt->fetchAll(PDO::FETCH_ASSOC);

try {
    // Получаем все NPC с типом "животное", которые могут двигаться
    $stmt = $pdo->prepare("SELECT * FROM npc WHERE health > 0 AND npc_type = 'животное'");
    $stmt->execute();
    $npc_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($npc_list as $npc_item) {
        $npc_id = $npc_item['id'];
        $npc_location_id = $npc_item['location_id'];
        $last_location_id = $npc_item['last_location_id']; // Предыдущая локация

        // Получаем все доступные направления
        $stmt = $pdo->prepare("SELECT target_location_id FROM directions WHERE location_id = ?");
        $stmt->execute([$npc_location_id]);
        $directions = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Исключаем предыдущее место, если есть другие варианты
        if (count($directions) > 1) {
            $directions = array_diff($directions, [$last_location_id]);
        }

        if (!empty($directions)) {
            // Выбираем случайное направление
            $new_location_id = $directions[array_rand($directions)];

            // Обновляем местоположение NPC
            $stmt = $pdo->prepare("UPDATE npc SET location_id = ?, last_location_id = ? WHERE id = ?");
            $stmt->execute([$new_location_id, $npc_location_id, $npc_id]);

            // Добавляем уведомление о перемещении NPC в сессию
            $npc_move_notifications[] = "NPC {$npc_item['name']} переместился в локацию {$new_location_id}.";
        }
    }

    // Сохраняем уведомления в сессии
    $_SESSION['npc_move_notifications'] = $npc_move_notifications;

    // Возвращаем обновленные NPC данные и уведомления
    echo json_encode([
        'npcs' => $npc,
        'notifications' => $npc_move_notifications
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>
