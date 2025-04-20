<?php
require 'db.php';
session_start();

// Получаем данные игрока
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    die('Ошибка: пользователь не авторизован.');
}

// Получаем данные NPC и диалогов
$npc_id = $_GET['npc_id'] ?? null;
$dialog_key = $_GET['dialog_key'] ?? null;

if (!$npc_id || !$dialog_key) {
    die('Ошибка: недостающие параметры.');
}

// Получаем диалог NPC по ключу
$stmt = $pdo->prepare('SELECT * FROM dialogs WHERE npc_id = ? AND dialog_key = ?');
$stmt->execute([$npc_id, $dialog_key]);
$dialog = $stmt->fetch();

if (!$dialog) {
    die('Диалог не найден.');
}

// Обрабатываем ответ игрока
$response = $_GET['response'] ?? null;
if ($response) {
    // Проверяем, какой квест привязан к выбранному ответу
    $quest_column = 'quest_' . $response;
    $quest_id = $dialog[$quest_column] ?? null;

    if ($quest_id) {
        // Проверяем, не принят ли уже квест
        $stmt = $pdo->prepare('SELECT * FROM user_quests WHERE user_id = ? AND quest_id = ?');
        $stmt->execute([$user_id, $quest_id]);
        $existingQuest = $stmt->fetch();

        if (!$existingQuest) {
            // Добавляем квест в таблицу user_quests
            $stmt = $pdo->prepare('INSERT INTO user_quests (user_id, quest_id, status) VALUES (?, ?, "accepted")');
            $stmt->execute([$user_id, $quest_id]);
            echo 'Квест принят!';
        } else {
            echo 'Вы уже приняли этот квест.';
        }
    }

    // Переход к следующему диалогу
    $next_dialog_key = $dialog['next_' . $response];
    if ($next_dialog_key) {
        echo 'Переход к следующему диалогу: ' . htmlspecialchars($next_dialog_key);
        // Здесь можно вывести новый диалог, привязанный к ключу $next_dialog_key
    } else {
        echo 'Конец диалога.';
    }
} else {
    // Если нет ответа, выводим варианты ответов
    echo '<h3>' . htmlspecialchars($dialog['text']) . '</h3>';
    for ($i = 1; $i <= 4; $i++) {
        if ($dialog['response_' . $i]) {
            echo '<a href="?npc_id=' . $npc_id . '&dialog_key=' . $dialog_key . '&response=' . $i . '">' . htmlspecialchars($dialog['response_' . $i]) . '</a><br>';
        }
    }
}
?>
