<?php
require 'db.php';
session_start();

// Получаем ID текущего пользователя из сессии
$user_id = $_SESSION['user_id'] ?? null;

// Получаем информацию о пользователе
$user = null;
if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$user) {
    echo "Пользователь не найден.";
    exit;
}

$current_location_id = $user['location_id'] ?? null;
$is_admin = ($user['role'] ?? '') === 'admin';

// Если нет текущей локации — присваиваем первую доступную
if (!$current_location_id) {
    $stmt = $pdo->query("SELECT id FROM locations LIMIT 1");
    $first_location = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($first_location) {
        $current_location_id = $first_location['id'];
        $_SESSION['location_id'] = $current_location_id;
        header("Location: map.php");
        exit;
    } else {
        echo "Локации отсутствуют в базе.";
        exit;
    }
}

// Получаем информацию о текущей локации, включая zone_id
$stmt = $pdo->prepare("SELECT * FROM locations WHERE id = ?");
$stmt->execute([$current_location_id]);
$current_location = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$current_location) {
    echo "Локация не найдена.";
    exit;
}

$player_x = $current_location['x'];
$player_y = $current_location['y'];
$player_z = $current_location['z'];
$player_zone = $current_location['zone_id']; // Добавляем зону

$rows = 10;
$cols = 10;

$offset_x = $player_x - floor($cols / 2);
$offset_y = $player_y - floor($rows / 2);

// Загружаем только локации текущей зоны
$stmt = $pdo->prepare("SELECT * FROM locations WHERE zone_id = ? AND z = ? AND x BETWEEN ? AND ? AND y BETWEEN ? AND ?");
$stmt->execute([
    $player_zone, // Фильтр по зоне
    $player_z,
    $offset_x,
    $offset_x + $cols - 1,
    $offset_y,
    $offset_y + $rows - 1
]);
$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Создаём пустую карту
$map = array_fill(0, $rows, array_fill(0, $cols, null));

foreach ($locations as $location) {
    $x = $location['x'] - $offset_x;
    $y = $location['y'] - $offset_y;

    if ($x >= 0 && $x < $cols && $y >= 0 && $y < $rows) {
        $map[$y][$x] = $location;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="img/icona.ico" rel="shortcut icon">
    <link rel="stylesheet" href="CSS/styles.css">
    <title>Карта локаций</title>
    <style>

    </style>
</head>
<body>
<div class="pipboy-container">
    <?php require 'header.php'; ?>

    <p><b>Текущее местоположение:</b> <?= $current_location ? htmlspecialchars($current_location['name']) : 'Не определено' ?></p>
    <p><b>ID текущего пользователя:</b> <?= $user_id ?? 'Не авторизован' ?></p>
    <main class="pipboy-screen">
        <h1>Карта локаций</h1>

        <table>
            <?php
            for ($y = $rows - 1; $y >= 0; $y--) {
                echo '<tr>';
                for ($x = 0; $x < $cols; $x++) {
                    $cell = $map[$y][$x];
                    if ($cell) {
                        $class = strtolower($cell['type']);
                        if ($cell['id'] == $current_location_id) {
                            $class .= ' current-location';
                        }
                        if ($is_admin) {
                            $class .= ' admin-view';
                        }
                        echo '<td class="' . $class . '">' . htmlspecialchars($cell['name']) . '</td>';
                    } else {
                        echo '<td class="none"></td>';
                    }
                }
                echo '</tr>';
            }
            ?>
        </table>
    </main>

    <footer class="pipboy-footer">
        <div class="footer-left">
            <a href="game.php" class="description-button">в игру</a>
            <a href="location_description.php?location_id=<?= $current_location['id'] ?? '' ?>" class="description-button">О локации</a>
            <a href="inventory.php" class="description-button">Инвентарь</a>
            <a href="menu.php" class="back">Меню</a>
            <?php if ($is_admin): ?>
                <a href="map1.php" class="description-button">админкарта</a>
            <?php endif; ?>
        </div>
    </footer>

    <?php require 'footer.php'; ?>
</div>

<script>
    function updateMapCenter(playerX, playerY, mapWidth, mapHeight, cellSize) {
        // Вычисление смещения с учетом размера карты
        const offsetX = Math.max(0, Math.min(playerX * cellSize - mapWidth / 2, (mapWidth - 1) * cellSize));
        const offsetY = Math.max(0, Math.min(playerY * cellSize - mapHeight / 2, (mapHeight - 1) * cellSize));

        const mapElement = document.querySelector('.pipboy-screen table');
        mapElement.style.transform = `translate(${ -offsetX }px, ${ -offsetY }px)`;
    }



</script>

</body>
</html>
