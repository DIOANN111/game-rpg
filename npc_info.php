<?php
require 'db.php';
session_start();

// Проверяем, передан ли ID NPC
if (!isset($_GET['id'])) {
    die("NPC не найден.");
}

$npc_id = (int) $_GET['id'];

// Получаем информацию о NPC
$stmt = $pdo->prepare("SELECT * FROM npc WHERE id = ?");
$stmt->execute([$npc_id]);
$npc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$npc) {
    die("NPC не найден.");
}

// Получаем дополнительные данные (например, инвентарь или связанные объекты)
$stmt = $pdo->prepare("SELECT * FROM dialogs WHERE npc_id = ? AND next_dialog_id IS NULL LIMIT 1");
$stmt->execute([$npc_id]);
$dialog = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Информация о NPC - <?= htmlspecialchars($npc['name']) ?></title>
    <link rel="stylesheet" href="CSS/styles.css">
</head>
<body>

<div class="pipboy-container">

    <!-- Пип-бой хедер -->
    <header class="pipboy-header">
        <div class="pipboy-logo">Моя Игра</div>
        <div class="pipboy-time"><?= date('H:i') ?></div>
    </header>

    <div class="pipboy-screen">
        <h1>Информация о NPC</h1>

        <!-- Отображаем NPC -->
        <div class="game-cards">
            <div class="game-card">
                <h2><?= htmlspecialchars($npc['name']) ?></h2>
                <p class="game-description"><?= htmlspecialchars($npc['description']) ?></p>

                <!-- Кнопки для действий с NPC -->
                <div class="button-container">
                    <!-- Кнопка для начала диалога с NPC -->
                    <a href="talk.php?npc_id=<?= $npc['id'] ?>" class="game-button">Поговорить</a>

                    <!-- Кнопка для получения дополнительной информации -->
                    <a href="npc_info.php?npc_id=<?= $npc['id'] ?>" class="game-button">Информация</a>

                    <!-- Кнопка для возвращения на предыдущую страницу с локацией NPC -->
                    <a href="game.php?location_id=<?= $npc['location_id'] ?>" class="back">Назад к локации</a>
                </div>
            </div>
        </div>

    </div>

</div>

</body>
</html>
