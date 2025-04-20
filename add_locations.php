<?php
require 'db.php';
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Получаем текущую локацию игрока
    $stmt = $pdo->prepare("SELECT location_id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !$user['location_id']) {
        die('Ошибка: локация не найдена.');
    }

    $current_location_id = $user['location_id'];

    // Функция для получения текущих координат
    function getCurrentCoordinates($pdo, $location_id) {
        $stmt = $pdo->prepare("SELECT x, y, z FROM locations WHERE id = ?");
        $stmt->execute([$location_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Получаем текущие координаты игрока
    $coordinates = getCurrentCoordinates($pdo, $current_location_id);
    $x = $coordinates['x'];
    $y = $coordinates['y'];
    $z = $coordinates['z'];

    // Карта направления и изменения координат
    $direction_map = [
        'north' => ['x' => 0, 'y' => 1, 'z' => 0, 'opposite' => 'south'],
        'south' => ['x' => 0, 'y' => -1, 'z' => 0, 'opposite' => 'north'],
        'east' => ['x' => 1, 'y' => 0, 'z' => 0, 'opposite' => 'west'],
        'west' => ['x' => -1, 'y' => 0, 'z' => 0, 'opposite' => 'east'],
        'up' => ['x' => 0, 'y' => 0, 'z' => 1, 'opposite' => 'down'],
        'down' => ['x' => 0, 'y' => 0, 'z' => -1, 'opposite' => 'up']
    ];

    // Инициализация направлений
    $direction_names = [
        'north' => 'Север',
        'south' => 'Юг',
        'west' => 'Запад',
        'east' => 'Восток',
        'up' => 'Вверх',
        'down' => 'Вниз'
    ];

    // Получение списка всех зон
    $stmt = $pdo->query("SELECT id, name FROM zones");
    $zones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Обработка запроса на создание локации
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $new_location_name = trim($_POST['location_name']);
        $description = $_POST['description'];
        $directions = $_POST['directions'] ?? [];
        $zone_id = $_POST['zone_id'] ?? null;
        $zone_name = $_POST['zone_name'] ?? null;
        $zone_types = $_POST['zone_type'] ?? [];

        if (empty($new_location_name) || empty($directions)) {
            $message = "Заполните название, описание и выберите хотя бы одно направление.";
        } else {
            try {
                // Если зона не выбрана, создаём новую
                if (empty($zone_id) && !empty($zone_name)) {
                    $stmt = $pdo->prepare("INSERT INTO zones (name, type) VALUES (?, ?)");
                    $stmt->execute([$zone_name, implode(',', $zone_types)]);
                    $zone_id = $pdo->lastInsertId();
                }

                // Создание локаций по направлениям
                foreach ($directions as $direction) {
                    if (isset($direction_map[$direction])) {
                        // Вычисляем координаты новой локации
                        $new_x = $x + $direction_map[$direction]['x'];
                        $new_y = $y + $direction_map[$direction]['y'];
                        $new_z = $z + $direction_map[$direction]['z'];

                        // Сохраняем новую локацию
                        $stmt = $pdo->prepare("INSERT INTO locations (name, description, x, y, z, zone_id) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$new_location_name, $description, $new_x, $new_y, $new_z, $zone_id]);
                        $location_id = $pdo->lastInsertId();

                        // Связываем направления (новая локация → старая)
                        $opposite = $direction_map[$direction]['opposite'];
                        $stmt = $pdo->prepare("INSERT INTO directions (location_id, direction_name, opposite_direction_name) VALUES (?, ?, ?)");
                        $stmt->execute([$location_id, $opposite, $direction]);

                        // Связываем направления (старая локация → новая)
                        $stmt = $pdo->prepare("INSERT INTO directions (location_id, direction_name, opposite_direction_name) VALUES (?, ?, ?)");
                        $stmt->execute([$current_location_id, $direction, $opposite]);
                    }
                }

                // Перенаправление на главную
                header("Location: index.php");
                exit;
            } catch (Exception $e) {
                $message = "Ошибка при создании локации: " . $e->getMessage();
            }
        }
    }

} catch (PDOException $e) {
    die('Ошибка базы данных: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Создание локации</title>
    <link rel="stylesheet" href="CSS/styles.css">
</head>
<body>

<div class="container">
    <h1>Создание новой локации</h1>

    <?php if (!empty($message)): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="location_name">Название локации:</label>
        <input type="text" id="location_name" name="location_name" required>

        <label for="description">Описание локации:</label>
        <textarea name="description" id="description" rows="4" required></textarea>

        <label for="x">Координата X:</label>
        <input type="number" name="x" id="x" value="<?= $x ?>" readonly>

        <label for="y">Координата Y:</label>
        <input type="number" name="y" id="y" value="<?= $y ?>" readonly>

        <label for="z">Координата Z:</label>
        <input type="number" name="z" id="z" value="<?= $z ?>" readonly>

        <label for="directions">Выберите направления:</label><br>
        <?php foreach ($direction_names as $key => $direction): ?>
            <input type="checkbox" name="directions[]" value="<?= $key ?>"> <?= $direction ?><br>
        <?php endforeach; ?>

        <label for="zone_id">Выберите зону:</label>
        <select name="zone_id" id="zone_id">
            <option value="">Выберите зону</option>
            <?php foreach ($zones as $zone): ?>
                <option value="<?= $zone['id'] ?>"><?= htmlspecialchars($zone['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <h2>Информация о зоне</h2>
        <label for="zone_name">Название зоны:</label>
        <input type="text" id="zone_name" name="zone_name"><br><br>

        <label for="zone_type">Тип зоны:</label><br>
        <input type="checkbox" id="pve" name="zone_type[]" value="PvE">
        <label for="pve">PvE</label><br>
        <input type="checkbox" id="pvp" name="zone_type[]" value="PvP">
        <label for="pvp">PvP</label><br>
        <input type="checkbox" id="protected" name="zone_type[]" value="Охраняемая">
        <label for="protected">Охраняемая</label><br><br>

        <input type="submit" value="Создать локацию">
    </form>
</div>

</body>
</html>
