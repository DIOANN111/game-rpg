<?php
// /functions/playerFunctions.php

include_once 'db.php';  // Подключаем конфигурацию базы данных

function trainSkill($userId, $skill, $amount) {
    global $pdo;

    // Получаем текущие данные игрока
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    // Обновляем выбранный навык
    if ($skill === 'combat_skill') {
        $newSkill = $user['combat_skill'] + $amount;
        $stmt = $pdo->prepare("UPDATE users SET combat_skill = ? WHERE id = ?");
        $stmt->execute([$newSkill, $userId]);
    } elseif ($skill === 'crafting_skill') {
        $newSkill = $user['crafting_skill'] + $amount;
        $stmt = $pdo->prepare("UPDATE users SET crafting_skill = ? WHERE id = ?");
        $stmt->execute([$newSkill, $userId]);
    }

    // Логируем событие
    $stmt = $pdo->prepare("INSERT INTO events (user_id, event_type, event_description, event_result) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, 'training', "Тренировка навыка $skill", "Увеличение на $amount"]);
}

function levelUp($userId) {
    global $pdo;

    // Получаем текущие данные игрока
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    // Если опыт больше или равен 100, повышаем уровень
    if ($user['experience'] >= 100) {
        $newLevel = $user['level'] + 1;
        $newExperience = $user['experience'] - 100; // Сбрасываем опыт

        // Обновляем уровень
        $stmt = $pdo->prepare("UPDATE users SET level = ?, experience = ? WHERE id = ?");
        $stmt->execute([$newLevel, $newExperience, $userId]);

        // Логируем событие
        $stmt = $pdo->prepare("INSERT INTO events (user_id, event_type, event_description, event_result) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, 'level_up', "Повышение уровня", "Уровень $newLevel"]);
    }
}

function takeDamage($userId, $damage) {
    global $pdo;

    // Получаем текущие данные игрока
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    // Уменьшаем здоровье игрока
    $newHealth = max(0, $user['health'] - $damage); // Не ниже 0

    // Обновляем здоровье в базе данных
    $stmt = $pdo->prepare("UPDATE users SET health = ? WHERE id = ?");
    $stmt->execute([$newHealth, $userId]);

    // Логируем событие
    $stmt = $pdo->prepare("INSERT INTO events (user_id, event_type, event_description, event_result) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, 'damage_taken', "Получено повреждение", "Здоровье: $newHealth"]);
}

function heal($userId, $amount) {
    global $pdo;

    // Получаем текущие данные игрока
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    // Лечим игрока (не выше максимального здоровья)
    $newHealth = min($user['max_health'], $user['health'] + $amount);

    // Обновляем здоровье в базе данных
    $stmt = $pdo->prepare("UPDATE users SET health = ? WHERE id = ?");
    $stmt->execute([$newHealth, $userId]);

    // Логируем событие
    $stmt = $pdo->prepare("INSERT INTO events (user_id, event_type, event_description, event_result) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, 'healed', "Лечение", "Здоровье: $newHealth"]);
}
?>
