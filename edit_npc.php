<?php
// edit_npc.php

session_start();
require 'db.php';

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Перенаправление на страницу логина, если не авторизован
    exit;
}

// Проверяем, передан ли ID NPC/врага для редактирования
if (!isset($_GET['id'])) {
    header('Location: characters.php'); // Перенаправление на страницу персонажей, если ID не передан
    exit;
}

// Получаем данные NPC/врага из базы данных
$id = $_GET['id'];
$sql = "SELECT * FROM npc WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$npc = $stmt->fetch();

// Если NPC/враг не найден, перенаправляем на страницу персонажей
if (!$npc) {
    header('Location: characters.php');
    exit;
}

// Устанавливаем значение по умолчанию для npc_type, если оно отсутствует
if (!isset($npc['npc_type'])) {
    $npc['npc_type'] = 'npc';  // Значение по умолчанию
}

// Получаем список зон для животных
$zones_sql = "SELECT * FROM zones";
$zones_stmt = $pdo->prepare($zones_sql);
$zones_stmt->execute();
$zones = $zones_stmt->fetchAll();

// Обрабатываем отправку формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из формы
    $name = $_POST['name'];
    $type = $_POST['npc_type'];
    $description = $_POST['description'];
    $location_id = $_POST['location_id'];

    // Получаем параметры для NPC
    $health = isset($_POST['health']) ? $_POST['health'] : null;
    $damage = isset($_POST['damage']) && $_POST['damage'] !== '' ? (int)$_POST['damage'] : 0;

    $weapon = isset($_POST['weapon']) ? $_POST['weapon'] : null;
    $zone_id = isset($_POST['zone_id']) ? $_POST['zone_id'] : null;

    // Проверяем, что обязательные поля не пустые
    if (!empty($name) && isset($location_id)) {
        // Если враг, проверяем дополнительные параметры
        if ($type === 'enemy' && (empty($damage) || empty($weapon))) {
            $error = "Пожалуйста, заполните все поля для врага (урон, оружие).";
        }

        // Если животное, проверяем, выбрана ли зона
        if ($type === 'животное' && empty($zone_id)) {
            $error = "Пожалуйста, выберите зону для животного.";
        }

        // Если все условия выполнены, обновляем данные NPC/врага в базе данных
        if (!isset($error)) {
            $sql = "UPDATE npc SET name = ?, npc_type = ?, description = ?, location_id = ?, health = ?, damage = ?, weapon = ?, zone_id = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $type, $description, $location_id, $health, $damage, $weapon, $zone_id, $id]);

            // Перенаправляем на страницу персонажей после успешного обновления
            header('Location: characters.php');
            exit;
        }
    } else {
        $error = "Пожалуйста, заполните все обязательные поля.";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать NPC/Врага</title>
    <style>
        /* Ваши стили */
    </style>
</head>
<body>

<h1>Редактировать NPC/Врага</h1>

<div class="form-container">
    <?php if (isset($error)): ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>

    <form action="edit_npc.php?id=<?= $npc['id'] ?>" method="POST">
        <div class="form-group">
            <label for="name">Имя NPC/Врага</label><br>
            <input type="text" name="name" id="name" class="form-input" value="<?= $npc['name'] ?>" required>
        </div>

        <div class="form-group">
            <label for="npc_type">Тип (Продавец NPC, NPC, Враг или Животное)</label><br>
            <select name="npc_type" id="npc_type" class="form-input" required>
                <option value="npc" <?= $npc['npc_type'] === 'npc' ? 'selected' : '' ?>>NPC</option>
                <option value="enemy" <?= $npc['npc_type'] === 'enemy' ? 'selected' : '' ?>>Враг</option>
                <option value="vendor" <?= $npc['npc_type'] === 'vendor' ? 'selected' : '' ?>>Продавец</option>
                <option value="animal" <?= $npc['npc_type'] === 'animal' ? 'selected' : '' ?>>Животное</option>
            </select>
        </div>

        <div class="form-group">
            <label for="description">Описание</label><br>
            <textarea name="description" id="description" class="form-input" rows="4" cols="30"><?= $npc['description'] ?></textarea>
        </div>

        <div class="form-group">
            <label for="location_id">ID локации</label><br>
            <input type="number" name="location_id" id="location_id" class="form-input" value="<?= $npc['location_id'] ?>" required>
        </div>

        <!-- Поле здоровья для всех типов NPC -->
        <div class="form-group">
            <label for="health">Здоровье</label><br>
            <input type="number" name="health" id="health" class="form-input" value="<?= $npc['health'] ?>" required>
        </div>

        <!-- Дополнительные поля для врага -->
        <div id="enemyFields" style="display: <?= $npc['npc_type'] === 'enemy' ? 'block' : 'none' ?>;">
            <div class="form-group">
                <label for="damage">Урон</label><br>
                <input type="number" name="damage" id="damage" class="form-input" value="<?= $npc['damage'] ?>">
            </div>

            <div class="form-group">
                <label for="weapon">Оружие</label><br>
                <input type="text" name="weapon" id="weapon" class="form-input" value="<?= $npc['weapon'] ?>">
            </div>
        </div>

        <!-- Дополнительные поля для животного -->
        <div id="animalFields" style="display: <?= $npc['npc_type'] === 'animal' ? 'block' : 'none' ?>;">
            <div class="form-group">
                <label for="zone_id">Выберите зону для животного</label><br>
                <select name="zone_id" id="zone_id" class="form-input">
                    <option value="">Выберите зону</option>
                    <?php foreach ($zones as $zone): ?>
                        <option value="<?= $zone['id'] ?>" <?= $npc['zone_id'] === $zone['id'] ? 'selected' : '' ?>>
                            <?= $zone['name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <button type="submit" class="form-button">Сохранить изменения</button>
    </form>

    <a href="characters.php" class="form-button">Назад</a>
</div>

<script>
    // Показываем дополнительные поля для врага и животного
    document.getElementById('npc_type').addEventListener('change', function () {
        var type = this.value;
        var enemyFields = document.getElementById('enemyFields');
        var animalFields = document.getElementById('animalFields');
        if (type === 'enemy') {
            enemyFields.style.display = 'block';
            animalFields.style.display = 'none';
        } else if (type === 'animal') {
            animalFields.style.display = 'block';
            enemyFields.style.display = 'none';
        } else {
            enemyFields.style.display = 'none';
            animalFields.style.display = 'none';
        }
    });
</script>

</body>
</html>
