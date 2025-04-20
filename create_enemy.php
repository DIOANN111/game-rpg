<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Получаем данные из формы
    $name = $_POST['name'];
    $description = $_POST['description'];
    $location_id = $_POST['location_id'];
    $dialogue = $_POST['dialogue'] ?? null; // Диалог не обязателен для животного
    $npc_type = $_POST['npc_type'];
    $health = $_POST['health'] ?? 100;
    $strength = $_POST['strength'] ?? 10;
    $damage = ($npc_type === 'враг' && isset($_POST['damage']) && $_POST['damage'] !== '') ? $_POST['damage'] : null; // Урон для врага
    $coins = ($npc_type === 'торговец' && isset($_POST['coins']) && $_POST['coins'] !== '') ? $_POST['coins'] : null; // Монеты для торговца
    $friendStrength = $_POST['friendStrength'] ?? null; // Сила друга
    $animalType = $_POST['animalType'] ?? null; // Тип животного

    // Проверка на уникальность имени NPC
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM npc WHERE name = ?");
    $stmt_check->execute([$name]);
    if ($stmt_check->fetchColumn() > 0) {
        echo "NPC с таким именем уже существует!";
        exit;
    }

    // Вставка данных в таблицу
    $stmt = $pdo->prepare("INSERT INTO npc 
                       (name, description, location_id, dialogue, npc_type, health, strength, damage, coins, friendStrength, animalType) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $name,
        $description,
        $location_id,
        $dialogue, // Диалог может быть null для животного
        $npc_type,
        $health,
        $strength,
        $damage, // Урон может быть null, если NPC не враг
        $coins,
        ($npc_type == 'друг' ? $friendStrength : NULL),
        ($npc_type == 'животное' ? $animalType : NULL)
    ]);

    // Выводим сообщение о том, что NPC создан
    echo "NPC \"$name\" успешно создан!";
    header("Location: game.php"); // Перенаправление на страницу списка NPC
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/styles.css">
    <title>Создание NPC</title>
</head>
<body>
<h1>Создать нового NPC</h1>
<form method="POST">
    <label for="npc_type">Тип NPC:</label>
    <select id="npc_type" name="npc_type" required>
        <option value="враг">Враг</option>
        <option value="торговец">Торговец</option>
        <option value="друг">Друг</option>
        <option value="животное">Животное</option> <!-- Новый тип NPC -->
    </select><br><br>

    <label for="name">Имя NPC:</label>
    <input type="text" id="name" name="name" required><br><br>

    <label for="description">Описание NPC:</label>
    <textarea id="description" name="description" required></textarea><br><br>

    <label for="location_id">ID локации:</label>
    <input type="number" id="location_id" name="location_id" required><br><br>

    <!-- Добавляем поля для врага, торговца, друга и животного -->
    <div id="enemyFields" style="display:none;">
        <label for="damage">Урон (для врага):</label>
        <input type="number" id="damage" name="damage" min="0"><br><br>
    </div>
    <div id="merchantFields" style="display:none;">
        <label for="coins">Количество монет (для торговца):</label>
        <input type="number" id="coins" name="coins" min="0"><br><br>
    </div>
    <div id="friendFields" style="display:none;">
        <label for="friendStrength">Сила друга:</label>
        <input type="number" id="friendStrength" name="friendStrength" min="1" value="5"><br><br>
    </div>
    <div id="animalFields" style="display:none;">
        <label for="animalType">Тип животного:</label>
        <input type="text" id="animalType" name="animalType" placeholder="Например, волк, медведь"><br><br>
    </div>

    <label for="health">Здоровье:</label>
    <input type="number" id="health" name="health" value="100"><br><br>

    <label for="strength">Сила:</label>
    <input type="number" id="strength" name="strength" value="10"><br><br>

    <button type="submit">Создать NPC</button>
    <a href="characters.php">Назад</a>
</form>

<script>
    // JavaScript для отображения дополнительных полей в зависимости от типа NPC
    const npcTypeSelect = document.getElementById('npc_type');
    const enemyFields = document.getElementById('enemyFields');
    const merchantFields = document.getElementById('merchantFields');
    const friendFields = document.getElementById('friendFields');
    const animalFields = document.getElementById('animalFields');

    npcTypeSelect.addEventListener('change', () => {
        if (npcTypeSelect.value === 'враг') {
            enemyFields.style.display = 'block';
            merchantFields.style.display = 'none';
            friendFields.style.display = 'none';
            animalFields.style.display = 'none';
            document.getElementById('damage').required = true;
            document.getElementById('coins').required = false;
            document.getElementById('dialogue').required = false;
        } else if (npcTypeSelect.value === 'торговец') {
            enemyFields.style.display = 'none';
            merchantFields.style.display = 'block';
            friendFields.style.display = 'none';
            animalFields.style.display = 'none';
            document.getElementById('damage').required = false;
            document.getElementById('coins').required = true;
            document.getElementById('dialogue').required = false;
        } else if (npcTypeSelect.value === 'друг') {
            enemyFields.style.display = 'none';
            merchantFields.style.display = 'none';
            friendFields.style.display = 'block';
            animalFields.style.display = 'none';
            document.getElementById('damage').required = false;
            document.getElementById('coins').required = false;
            document.getElementById('dialogue').required = true;
        } else if (npcTypeSelect.value === 'животное') {
            enemyFields.style.display = 'none';
            merchantFields.style.display = 'none';
            friendFields.style.display = 'none';
            animalFields.style.display = 'block';
            document.getElementById('damage').required = false;
            document.getElementById('coins').required = false;
            document.getElementById('dialogue').required = false;
        }
    });

    // Устанавливаем начальное состояние
    npcTypeSelect.dispatchEvent(new Event('change'));
</script>

</body>
</html>
