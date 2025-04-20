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

// Получаем информацию о текущей локации
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

$rows = 10;
$cols = 10;

$offset_x = $player_x - floor($cols / 2);
$offset_y = $player_y - floor($rows / 2);

// Загружаем локации в пределах карты
$stmt = $pdo->prepare("SELECT * FROM locations WHERE z = ? AND x BETWEEN ? AND ? AND y BETWEEN ? AND ?");
$stmt->execute([
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
    <title>Карта локаций</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background-color: #1a1a1a;
            color: #00ff00;
            margin: 0;
            padding: 0;
        }

        .pipboy-container {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            border: 2px solid #00ff00;
            border-radius: 10px;
            background-color: #222;
            box-shadow: 0 0 15px rgba(0, 255, 0, 0.5);
        }

        .pipboy-header {
            display: flex;
            justify-content: space-between;
            padding: 15px;
            background-color: #333;
            border-bottom: 2px solid #00ff00;
        }

        .pipboy-logo {
            font-size: 24px;
            font-weight: bold;
            color: #00ff00;
        }

        .pipboy-time {
            font-size: 18px;
        }

        .pipboy-nav {
            background-color: #333;
            padding: 10px;
            text-align: center;
        }

        .nav-button {
            margin: 0 10px;
            text-decoration: none;
            color: #00ff00;
            font-size: 16px;
            font-weight: bold;
        }

        .nav-button:hover {
            color: #222;
            background-color: #00ff00;
            padding: 5px 10px;
            border-radius: 5px;
        }

        .pipboy-screen {
            padding: 20px;
            text-align: center;
        }

        .pipboy-screen h1 {
            font-size: 32px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: separate;  /* Отключаем слияние границ */
            border-spacing: 10px;  /* Добавляем расстояние между клетками */
        }

        td {
            width: 0.5%;
            height: 30px;
            text-align: center;
            vertical-align: middle;
            border: 1px solid #333;  /* Граница для каждой ячейки */
            background-color: #333;
            color: #00ff00;
            font-size: 12px;
        }

        .current-location {
            background-color: #ff4500;
            color: #fff;
        }

        .exitloc {
            background-color: #333;
            color: #00ff00;
        }

        .none {
            background-color: #222;
        }

        .doroga {
            background-color: #444;
            color: #fff;
        }

        .meadow {
            background-color: #2e8b57;
            color: #fff;
        }

        .cave {
            background-color: #8b4513;
            color: #fff;
        }

        .kamvosk {
            background-color: #d2691e;
            color: #fff;
        }

        .heropos {
            background-color: #ff0000;
            color: #fff;
        }

        .pipboy-footer {
            background-color: #333;
            padding: 15px;
            border-top: 2px solid #00ff00;
            display: flex;
            justify-content: space-between;
            font-size: 14px;
        }

        .footer-left {
            color: #a0a0a0;
        }

        .footer-right {
            text-align: right;
        }

        .footer-left a, .footer-right a {
            color: #00ff00;
            text-decoration: none;
        }

        .footer-left a:hover, .footer-right a:hover {
            text-decoration: underline;
        }

        .npc-list, .items-list {
            margin-top: 30px;
        }

        .npc-item, .item {
            background-color: #444;
            padding: 10px;
            margin: 5px;
            color: #fff;
            border-radius: 8px;
        }

        .npc-button, .direction-button, .chat-button, .back, .description-button {
            background: none;
            border: none;
            color: #00ff00;
            font-size: 18px;
            text-decoration: none;
            cursor: pointer;
            display: block;
            width: 90%;
            text-align: left;
            padding: 10px;
            background-color: #444;
            border-radius: 8px;
            margin: 5px 0;
            transition: background-color 0.3s;
        }

        .npc-button:hover, .direction-button:hover, .chat-button:hover, .back:hover, .description-button:hover {
            background-color: #00ff00;
            color: #222;
        }

        .back {
            display: block;
            margin-top: 30px;
        }

        .chat-button {
            margin-top: 30px;
        }

        .map-legend {
            background-color: #333;
            padding: 15px;
            margin-top: 30px;
            border-radius: 10px;
        }

        .map-legend h3 {
            color: #00ff00;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .map-legend ul {
            list-style-type: none;
            padding: 0;
        }

        .map-legend li {
            margin-bottom: 10px;
            font-size: 16px;
        }

        .map-legend span {
            display: inline-block;
            width: 20px;
            height: 20px;
            margin-right: 10px;
            text-align: center;
            font-size: 18px;
        }

        .map-legend li span.doroga {
            background-color: #444;
            color: white;
        }

        .map-legend li span.meadow {
            background-color: #2e8b57;
            color: white;
        }

        .map-legend li span.cave {
            background-color: #8b4513;
            color: white;
        }

        .map-legend li span.kamvosk {
            background-color: #d2691e;
            color: white;
        }

        .map-legend li span.none {
            background-color: #222;
            color: white;
        }

        .map-legend li span.current-location {
            background-color: #ff4500;
            color: white;
        }
    </style>
</head>
<body>
<div class="pipboy-container">

    <?php require 'header.php'; ?>
    <p><b>Текущее местоположение:</b> <?= $current_location ? htmlspecialchars($current_location['name']) : 'Не определено' ?></p>
    <main class="pipboy-screen">
        <h1>Карта локаций</h1>

        <table>
            <?php
            // Отображаем карту
            for ($y = $rows - 1; $y >= 0; $y--) { // Итерируем по строкам снизу вверх
                echo '<tr>';
                for ($x = 0; $x < $cols; $x++) {
                    $cell = $map[$y][$x];
                    if ($cell) {
                        // Добавляем класс в зависимости от типа локации
                        $class = strtolower($cell['type']);
                        // Выделяем текущую локацию
                        if ($cell['id'] == $current_location_id) {
                            $class .= ' current-location';
                        }
                        // Добавляем координаты в атрибуты
                        echo '<td class="' . $class . '" data-id="' . $cell['id'] . '" data-x="' . $x . '" data-y="' . $y . '">' . htmlspecialchars($cell['name']) . '</td>';
                    } else {
                        // Если локации нет, отображаем пустую клетку
                        echo '<td class="none" data-id="" data-x="' . $x . '" data-y="' . $y . '"></td>';
                    }
                }
                echo '</tr>';
            }
            ?>
        </table>

        <!-- Место для отображения координат -->
        <p id="coordinates-display"><b>Координаты: </b>Не выбраны</p>

        <hr>
    </main>

    <footer class="pipboy-footer">
        <div class="footer-left">
            <a href="game.php" class="description-button">в игру</a>
            <a href="location_description.php?location_id=<?= $current_location['id'] ?? '' ?>" class="description-button">О локации</a>
            <a href="inventory.php" class="description-button">Инвентарь</a>
            <a href="menu.php" class="back">Меню</a>
            <a href="chat.php" class="chat-button">Общий чат</a>
            <a href="map1.php" class="description-button">Карта</a>
        </div>
    </footer>

    <?php require 'footer.php'; ?>
</div>

<!-- Добавляем JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const cells = document.querySelectorAll('td'); // Получаем все ячейки таблицы

        cells.forEach(cell => {
            cell.addEventListener('click', function () {
                // Получаем координаты из атрибутов data-x и data-y
                const x = this.getAttribute('data-x');
                const y = this.getAttribute('data-y');

                // Находим элемент для отображения координат
                const coordinatesDisplay = document.getElementById('coordinates-display');
                coordinatesDisplay.innerHTML = `<b>Координаты: </b> X: ${x}, Y: ${y}`; // Выводим координаты

                // Перенаправление на страницу добавления локации с выбранными координатами
                window.location.href = `add_location.php?x=${x}&y=${y}`;
            });
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        const cells = document.querySelectorAll('td'); // Получаем все ячейки таблицы

        cells.forEach(cell => {
            cell.addEventListener('click', function () {
                // Получаем ID локации
                const id = this.getAttribute('data-id');
                if (id) {
                    window.location.href = `edit_location_form.php?id=${id}`; // Перенаправляем на страницу редактирования
                }
            });
        });
    });
</script>

</body>
</html>
