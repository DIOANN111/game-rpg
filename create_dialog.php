<?php
require 'db.php';
session_start();

// Получаем всех NPC
$stmt = $pdo->query('SELECT * FROM npc');
$npcs = $stmt->fetchAll();

// Получаем все квесты
$stmt = $pdo->query('SELECT * FROM quests');
$quests = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $npc_id = $_POST['npc_id'];
    $dialog_key = $_POST['dialog_key'];
    $text = $_POST['text'];
    $response_1 = $_POST['response_1'] ?: null;
    $next_1 = $_POST['next_1'] ?: null;
    $quest_1 = $_POST['quest_1'] ?: null;
    $response_2 = $_POST['response_2'] ?: null;
    $next_2 = $_POST['next_2'] ?: null;
    $quest_2 = $_POST['quest_2'] ?: null;
    $response_3 = $_POST['response_3'] ?: null;
    $next_3 = $_POST['next_3'] ?: null;
    $quest_3 = $_POST['quest_3'] ?: null;
    $response_4 = $_POST['response_4'] ?: null;
    $next_4 = $_POST['next_4'] ?: null;
    $quest_4 = $_POST['quest_4'] ?: null;

    if (!empty($dialog_key) && !empty($text)){
        $stmt = $pdo->prepare('INSERT INTO dialogs 
            (npc_id, dialog_key, text, response_1, next_1, quest_1, response_2, next_2, quest_2, response_3, next_3, quest_3, response_4, next_4, quest_4) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$npc_id, $dialog_key, $text, $response_1, $next_1, $quest_1, $response_2, $next_2, $quest_2, $response_3, $next_3, $quest_3, $response_4, $next_4, $quest_4]);
        echo 'Диалог добавлен';
    } else {
        echo 'Заполните все обязательные поля';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Создание диалога</title>
</head>
<body>
<h2>Создать диалог</h2>
<form method="post">
    <label>Выберите NPC:
        <select name="npc_id">
            <?php foreach ($npcs as $npc): ?>
                <option value="<?= $npc['id'] ?>"><?= htmlspecialchars($npc['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label><br>

    <label>Ключ диалога:<input type="text" name="dialog_key" required></label><br>
    <label>Текст NPC:<br><textarea name="text" rows="3" required></textarea></label><br>

    <?php for ($i = 1; $i <= 4; $i++): ?>
        <h3>Вариант ответа <?= $i ?>:</h3>
        <label>Текст ответа:<input type="text" name="response_<?= $i ?>"></label><br>
        <label>Ключ следующего диалога:<input type="text" name="next_<?= $i ?>"></label><br>
        <label>Выдать квест:
            <select name="quest_<?= $i ?>">
                <option value="">-- Не выдавать квест --</option>
                <?php foreach ($quests as $quest): ?>
                    <option value="<?= $quest['id'] ?>"><?= htmlspecialchars($quest['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label><br>
    <?php endfor; ?>

    <button type="submit">Добавить диалог</button>
</form>
</body>
</html>
