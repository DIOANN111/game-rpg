<?php
require 'db.php';
session_start();

// Проверяем, передан ли ID NPC
if (!isset($_GET['npc_id'])) {
    die("Ошибка: нет идентификатора NPC.");
}

$npc_id = (int) $_GET['npc_id'];

// Получаем информацию о NPC
$stmt = $pdo->prepare("SELECT * FROM npc WHERE id = ?");
$stmt->execute([$npc_id]);
$npc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$npc) {
    die("NPC не найден.");
}

// Проверяем, что NPC является животным (не важно, прирученное или нет)
$is_animal = ($npc['npc_type'] == 'животное' || $npc['npc_type'] == 'животное_прирученное');
$is_tamed = ($npc['npc_type'] == 'животное_прирученное'); // Проверка на прирученное животное

// Логика приручения
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'tame' && $npc['npc_type'] == 'животное') {
        // Приручаем животное и связываем его с пользователем
        $user_id = $_SESSION['user_id']; // Получаем ID пользователя из сессии
        $stmt = $pdo->prepare("UPDATE npc SET follow_user_id = ?, npc_type = 'животное_прирученное' WHERE id = ?");
        $stmt->execute([$user_id, $npc_id]);

        // Увеличиваем навык друида
        $stmt = $pdo->prepare("UPDATE users SET druid_skill = druid_skill + 1 WHERE id = ?");
        $stmt->execute([$user_id]);

        echo "Вы приручили животное и улучшили навык друида!<br>";
    }

    // Если действие "Сменить кличку"
    if (isset($_POST['action']) && $_POST['action'] == 'rename') {
        $new_name = $_POST['name'];
        if (empty($new_name)) {
            die("Имя не может быть пустым.");
        }

        $stmt = $pdo->prepare("UPDATE npc SET name = ? WHERE id = ?");
        $stmt->execute([$new_name, $npc_id]);
        echo "Кличка изменена на " . htmlspecialchars($new_name) . "<br>";
    }

    // Если действие "Расстаться"
    if (isset($_POST['action']) && $_POST['action'] == 'part_with' && $npc['npc_type'] == 'животное_прирученное') {
        // Восстанавливаем оригинальное имя и тип NPC
        $stmt = $pdo->prepare("UPDATE npc SET npc_type = 'животное', follow_user_id = NULL, name = original_name WHERE id = ?");
        $stmt->execute([$npc_id]);

        // Убираем строку, уменьшающую навык друида
        // $user_id = $_SESSION['user_id']; // Получаем ID пользователя из сессии
        // $stmt = $pdo->prepare("UPDATE users SET druid_skill = druid_skill - 1 WHERE id = ?");
        // $stmt->execute([$user_id]);

        echo "Вы расстались с животным, оно стало свободным и вернуло свою первоначальную кличку.<br>";
    }

}


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

                <!-- Логика действий с NPC -->
                <div class="button-container">
                    <?php if ($npc['npc_type'] == 'враг'): ?>
                        <a href="attack.php?npc_id=<?= $npc['id'] ?>" class="game-button">Атаковать</a>
                    <?php elseif ($npc['npc_type'] == 'торговец'): ?>
                        <a href="trade.php?npc_id=<?= $npc['id'] ?>" class="game-button">Торговать</a>
                        <a href="talk.php?npc_id=<?= $npc['id'] ?>" class="game-button">Поговорить</a>
                    <?php elseif ($npc['npc_type'] == 'животное'): ?>
                        <!-- Если животное не приручено, показываем кнопку приручить -->
                        <form method="post">
                            <input type="hidden" name="action" value="tame">
                            <input type="submit" value="Приручить" class="game-button">
                        </form>
                    <?php elseif ($npc['npc_type'] == 'животное_прирученное'): ?>
                        <!-- Если животное приручено, показываем кнопки смены клички и расставания -->
                        <form method="post">
                            <input type="hidden" name="action" value="rename">
                            <label for="name">Новая кличка:</label>
                            <input type="text" name="name" id="name" required>
                            <input type="submit" value="Сменить кличку" class="game-button">
                        </form>

                        <form method="post">
                            <input type="hidden" name="action" value="part_with">
                            <input type="submit" value="Расстаться" class="game-button">
                        </form>
                    <?php else: ?>
                        <a href="talk.php?npc_id=<?= $npc['id'] ?>" class="game-button">Поговорить</a>
                    <?php endif; ?>
                    <a href="npc_info.php?npc_id=<?= $npc['id'] ?>" class="game-button">Информация</a>
                    <a href="game.php?location_id=<?= $npc['location_id'] ?>" class="back">Назад к локации</a>
                </div>
            </div>
        </div>

    </div>

</div>

</body>
</html>
