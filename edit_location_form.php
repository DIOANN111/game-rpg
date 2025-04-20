<?php
require 'db.php';

// Получаем ID локации из URL
$id = $_GET['id'];

// Получаем данные о локации из базы данных
$stmt = $pdo->prepare("SELECT * FROM locations WHERE id = ?");
$stmt->execute([$id]);
$location = $stmt->fetch();

// Если данные не найдены, перенаправляем на главную
if (!$location) {
    header('Location: index.php');
    exit;
}

// Переменная для сообщений об ошибке
$message = '';

// Получаем направления для данной локации
$stmt = $pdo->prepare("SELECT * FROM directions WHERE location_id = ?");
$stmt->execute([$id]);
$directions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$direction_names = [
    'north' => '',
    'south' => '',
    'west' => '',
    'east' => '',
    'up' => '',
    'down' => ''
];

$target_location_ids = [
    'north' => '',
    'south' => '',
    'west' => '',
    'east' => '',
    'up' => '',
    'down' => ''
];

// Преобразуем данные направлений в удобный формат
foreach ($directions as $direction) {
    $direction_names[$direction['direction_name']] = $direction['direction_name'];
    $target_location_ids[$direction['direction_name']] = $direction['target_location_id'];
}

// Получаем список всех локаций для выпадающих списков
$stmt = $pdo->prepare("SELECT id, name FROM locations");
$stmt->execute();
$all_locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Выводим переданные данные для отладки
    echo '<pre>';
    print_r($_POST); // Выводим все данные, переданные через форму
    echo '</pre>';

    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $x = $_POST['x'];
    $y = $_POST['y'];
    $z = $_POST['z'];

    if (empty($name) || empty($description)) {
        $message = "Название и описание не могут быть пустыми.";
    } else {
        // Получаем названия направлений и целевые локации
        $directions = [];

        if (!empty($_POST['north_name']) && !empty($_POST['north_target_location_id'])) {
            $directions['north'] = [
                'name' => $_POST['north_name'],
                'target_location_id' => $_POST['north_target_location_id']
            ];
        }

        if (!empty($_POST['south_name']) && !empty($_POST['south_target_location_id'])) {
            $directions['south'] = [
                'name' => $_POST['south_name'],
                'target_location_id' => $_POST['south_target_location_id']
            ];
        }
        if (!empty($_POST['west_name']) && !empty($_POST['west_target_location_id'])) {
            $directions['west'] = [
                'name' => $_POST['west_name'],
                'target_location_id' => $_POST['west_target_location_id']
            ];
        }
        if (!empty($_POST['east_name']) && !empty($_POST['east_target_location_id'])) {
            $directions['east'] = [
                'name' => $_POST['east_name'],
                'target_location_id' => $_POST['east_target_location_id']
            ];
        }
        if (!empty($_POST['up_name']) && !empty($_POST['up_target_location_id'])) {
            $directions['up'] = [
                'name' => $_POST['up_name'],
                'target_location_id' => $_POST['up_target_location_id']
            ];
        }
        if (!empty($_POST['down_name']) && !empty($_POST['down_target_location_id'])) {
            $directions['down'] = [
                'name' => $_POST['down_name'],
                'target_location_id' => $_POST['down_target_location_id']
            ];
        }

        // Проверим, что направления сформированы корректно
        if (empty($directions)) {
            $message = "Необходимо указать хотя бы одно направление.";
        } else {
            try {
                // Обновляем данные локации
                $stmt = $pdo->prepare("UPDATE locations SET name = ?, description = ?, x = ?, y = ?, z = ? WHERE id = ?");
                if ($stmt->execute([$name, $description, $x, $y, $z, $id])) {
                    echo 'Локация обновлена.'; // Отладочная информация
                } else {
                    echo 'Ошибка при обновлении локации.'; // Отладочная информация
                }

                // Обновляем или вставляем новые направления
                foreach ($directions as $direction => $data) {
                    $direction_name = $data['name'];
                    $target_location_id = $data['target_location_id'];

                    // Проверяем, существует ли уже это направление в базе данных
                    $stmt = $pdo->prepare("SELECT * FROM directions WHERE location_id = ? AND direction_name = ?");
                    $stmt->execute([$id, $direction]);

                    if ($stmt->rowCount() > 0) {
                        // Если существует, обновляем направление
                        $stmt = $pdo->prepare("UPDATE directions SET direction_name = ?, target_location_id = ? WHERE location_id = ? AND direction_name = ?");
                        $stmt->execute([$direction_name, $target_location_id, $id, $direction]);
                        echo "Направление $direction обновлено."; // Отладочная информация
                    } else {
                        // Если не существует, вставляем новое направление
                        $stmt = $pdo->prepare("INSERT INTO directions (location_id, direction_name, target_location_id) VALUES (?, ?, ?)");
                        $stmt->execute([$id, $direction_name, $target_location_id]);
                        echo "Направление $direction добавлено."; // Отладочная информация
                    }
                }

                header("Location: index.php");
                exit;
            } catch (Exception $e) {
                $message = "Ошибка при сохранении локации: " . $e->getMessage();
            }
        }
    }
}

// Получаем данные о соседних локациях для текущей локации
$neighboring_locations = [];

foreach ($direction_names as $direction => $direction_name) {
    if (!empty($direction_name) && !empty($target_location_ids[$direction])) {
        // Ищем информацию о соседней локации
        $stmt = $pdo->prepare("SELECT name FROM locations WHERE id = ?");
        $stmt->execute([$target_location_ids[$direction]]);
        $neighbor_location = $stmt->fetch();

        if ($neighbor_location) {
            $neighboring_locations[$direction] = $neighbor_location['name'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать локацию</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background-color: #1a1a1a;
            color: #00ff00;
            margin: 0;
            padding: 0;
        }

        .form-container {
            width: 100%;
            max-width: 400px;
            margin: 50px auto;
            border: 2px solid #00ff00;
            border-radius: 10px;
            background-color: #222;
            box-shadow: 0 0 15px rgba(0, 255, 0, 0.5);
            padding: 20px;
        }

        h1 {
            text-align: center;
        }

        label {
            display: block;
            margin-bottom: 10px;
        }

        input, textarea, select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 2px solid #00ff00;
            background-color: #333;
            color: #00ff00;
            border-radius: 5px;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #00ff00;
            color: #222;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background-color: #222;
            color: #00ff00;
        }

        .message {
            color: #ff0000;
            text-align: center;
        }

        .neighboring-locations {
            margin-top: 20px;
            padding: 15px;
            background-color: #333;
            border: 2px solid #00ff00;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 255, 0, 0.5);
        }

        .neighboring-locations h2 {
            text-align: center;
            color: #00ff00;
        }

        .neighboring-locations ul {
            list-style-type: none;
            padding: 0;
        }

        .neighboring-locations li {
            color: #00ff00;
            padding: 5px 0;
        }
    </style>
</head>
<body>

<h1>Редактировать локацию</h1>

<div class="form-container">
    <?php if ($message): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="name">Название локации</label>
        <input type="text" name="name" id="name" value="<?= htmlspecialchars($location['name']) ?>" required>

        <label for="description">Описание локации</label>
        <textarea name="description" id="description" rows="4" required><?= htmlspecialchars($location['description']) ?></textarea>

        <label for="x">Координата X</label>
        <input type="number" name="x" id="x" value="<?= htmlspecialchars($location['x']) ?>" required>

        <label for="y">Координата Y</label>
        <input type="number" name="y" id="y" value="<?= htmlspecialchars($location['y']) ?>" required>

        <label for="z">Координата Z</label>
        <input type="number" name="z" id="z" value="<?= htmlspecialchars($location['z']) ?>" required>

        <label for="north_name">Север (название)</label>
        <input type="text" name="north_name" id="north_name" value="<?= htmlspecialchars($direction_names['north']) ?>">

        <label for="south_name">Юг (название)</label>
        <input type="text" name="south_name" id="south_name" value="<?= htmlspecialchars($direction_names['south']) ?>">

        <label for="west_name">Запад (название)</label>
        <input type="text" name="west_name" id="west_name" value="<?= htmlspecialchars($direction_names['west']) ?>">

        <label for="east_name">Восток (название)</label>
        <input type="text" name="east_name" id="east_name" value="<?= htmlspecialchars($direction_names['east']) ?>">

        <label for="up_name">Вверх (название)</label>
        <input type="text" name="up_name" id="up_name" value="<?= htmlspecialchars($direction_names['up']) ?>">

        <label for="down_name">Вниз (название)</label>
        <input type="text" name="down_name" id="down_name" value="<?= htmlspecialchars($direction_names['down']) ?>">

        <!-- Добавлены дополнительные направления -->
        <label for="north_target_location_id">Север (целевая локация)</label>
        <select name="north_target_location_id" id="north_target_location_id">
            <option value="">Выберите локацию</option>
            <?php foreach ($all_locations as $loc): ?>
                <option value="<?= $loc['id'] ?>" <?= $loc['id'] == $target_location_ids['north'] ? 'selected' : '' ?>><?= $loc['name'] ?></option>
            <?php endforeach; ?>
        </select>

        <label for="south_target_location_id">Юг (целевая локация)</label>
        <select name="south_target_location_id" id="south_target_location_id">
            <option value="">Выберите локацию</option>
            <?php foreach ($all_locations as $loc): ?>
                <option value="<?= $loc['id'] ?>" <?= $loc['id'] == $target_location_ids['south'] ? 'selected' : '' ?>><?= $loc['name'] ?></option>
            <?php endforeach; ?>
        </select>

        <label for="west_target_location_id">Запад (целевая локация)</label>
        <select name="west_target_location_id" id="west_target_location_id">
            <option value="">Выберите локацию</option>
            <?php foreach ($all_locations as $loc): ?>
                <option value="<?= $loc['id'] ?>" <?= $loc['id'] == $target_location_ids['west'] ? 'selected' : '' ?>><?= $loc['name'] ?></option>
            <?php endforeach; ?>
        </select>

        <label for="east_target_location_id">Восток (целевая локация)</label>
        <select name="east_target_location_id" id="east_target_location_id">
            <option value="">Выберите локацию</option>
            <?php foreach ($all_locations as $loc): ?>
                <option value="<?= $loc['id'] ?>" <?= $loc['id'] == $target_location_ids['east'] ? 'selected' : '' ?>><?= $loc['name'] ?></option>
            <?php endforeach; ?>
        </select>

        <label for="up_target_location_id">Вверх (целевая локация)</label>
        <select name="up_target_location_id" id="up_target_location_id">
            <option value="">Выберите локацию</option>
            <?php foreach ($all_locations as $loc): ?>
                <option value="<?= $loc['id'] ?>" <?= $loc['id'] == $target_location_ids['up'] ? 'selected' : '' ?>><?= $loc['name'] ?></option>
            <?php endforeach; ?>
        </select>

        <label for="down_target_location_id">Вниз (целевая локация)</label>
        <select name="down_target_location_id" id="down_target_location_id">
            <option value="">Выберите локацию</option>
            <?php foreach ($all_locations as $loc): ?>
                <option value="<?= $loc['id'] ?>" <?= $loc['id'] == $target_location_ids['down'] ? 'selected' : '' ?>><?= $loc['name'] ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Сохранить изменения</button>
    </form>
</div>

<!-- Соседние локации -->
<div class="neighboring-locations">
    <h2>Соседние локации</h2>
    <ul>
        <?php foreach ($neighboring_locations as $direction => $neighbor_name): ?>
            <li><strong><?= ucfirst($direction) ?>:</strong> <?= htmlspecialchars($neighbor_name) ?></li>
        <?php endforeach; ?>
    </ul>
</div>

</body>
</html>
