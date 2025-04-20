<?php
// rab.php

// Проверка на рабство
function checkSlaveStatus($user_id, $pdo) {
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user['status'] === 'slave';
}

// Логика перемещения раба
function moveSlave($user_id, $location_id, $pdo) {
    // Перемещаем раба игрока, если есть
    $stmt = $pdo->prepare("SELECT slave_id FROM slavery WHERE master_id = ?");
    $stmt->execute([$user_id]);
    $slave = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($slave) {
        $stmt = $pdo->prepare("UPDATE users SET location_id = ? WHERE id = ?");
        $stmt->execute([$location_id, $slave['slave_id']]);
    }
}

?>
