<?php
require 'db.php';

// Установим лимит на респаун в 10 минут
$respawn_time_limit = 10 * 60; // 10 минут в секундах

// Получаем всех мертвых NPC, у которых время респауна истекло
$stmt = $pdo->prepare("SELECT id, last_respawn FROM npc WHERE is_dead = 1 AND UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(last_respawn) >= ?");
$stmt->execute([$respawn_time_limit]);
$npc_to_respawn = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Возвращаем NPC в игру
foreach ($npc_to_respawn as $npc_data) {
    // Например, восстанавливаем NPC с полным здоровьем
    $stmt = $pdo->prepare("UPDATE npc SET is_dead = 0, health = 100, last_respawn = NULL WHERE id = ?");
    $stmt->execute([$npc_data['id']]);

    // Дополнительно можно установить NPC на случайную позицию или использовать предыдущие координаты.
    error_log("NPC с ID {$npc_data['id']} респавнился.");
}
