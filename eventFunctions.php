<?php
// /functions/eventFunctions.php

include_once 'db.php';  // Подключаем конфигурацию базы данных

function logEvent($userId, $eventType, $eventDescription, $eventResult) {
    global $pdo;

    // Логируем событие
    $stmt = $pdo->prepare("INSERT INTO events (user_id, event_type, event_description, event_result) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $eventType, $eventDescription, $eventResult]);
}
?>
