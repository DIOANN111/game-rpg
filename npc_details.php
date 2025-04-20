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

// Отладка: Выводим тип NPC для проверки
echo "Тип NPC: " . htmlspecialchars($npc['npc_type']) . "<br>";

// Проверяем, является ли NPC прирученным животным
$is_tamed = ($npc['npc_type'] == 'животное_прирученное'); // Проверка на прирученность животного

// Отладка: Выводим, что проверка прошла успешно
echo "Приручен ли NPC: " . ($is_tamed ? "Да" : "Нет") . "<br>";
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
                    <?php if ($npc['npc_type'] == 'враг'): ?>
                        <a href="attack.php?npc_id=<?= $npc['id'] ?>" class="game-button">Атаковать</a>
                    <?php elseif ($npc['npc_type'] == 'торговец'): ?>
                        <a href="trade.php?npc_id=<?= $npc['id'] ?>" class="game-button">Торговать</a>
                        <a href="talk.php?npc_id=<?= $npc['id'] ?>" class="game-button">Поговорить</a>
                    <?php elseif ($npc['npc_type'] == 'животное' || $npc['npc_type'] == 'животное_прирученное'): ?>
                        <?php if ($is_tamed): ?>
                            <!-- Если животное приручено, показываем кнопки смены клички и расставания -->
                            <a href="tame.php?npc_id=<?= $npc['id'] ?>&action=rename" class="game-button">Сменить кличку</a>
                            <a href="tame.php?npc_id=<?= $npc['id'] ?>&action=part_with" class="game-button">Расстаться</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="talk.php?npc_id=<?= $npc['id'] ?>" class="game-button">Поговорить</a>
                    <?php endif; ?>

                    <!-- Информация ведет на tame.php вместо npc_info.php -->
                    <a href="tame.php?npc_id=<?= $npc['id'] ?>" class="game-button">Информация</a>

                    <a href="game.php?location_id=<?= $npc['location_id'] ?>" class="back">Назад к локации</a>
                </div>
            </div>
        </div>

    </div>

</div>

</body>
</html>
