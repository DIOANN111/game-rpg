    <?php
    require 'db.php';

    // Получение списка всех локаций
    $stmt = $pdo->query("SELECT id, name, x, y, z FROM locations");
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Проверка на обязательные поля
        if (!isset($_POST['name'], $_POST['description'], $_POST['x'], $_POST['y'], $_POST['z'])) {
            echo "Все поля должны быть заполнены.";
            exit;
        }

        $name = $_POST['name'];
        $description = $_POST['description'];
        $x = $_POST['x'];
        $y = $_POST['y'];
        $z = $_POST['z'];
        $action = $_POST['action'] ?? null;




        // Получаем данные о зоне
        $zone_id = $_POST['zone_id'] ?? null;
        $zone_name = $_POST['zone_name'] ?? null;
        $zone_types = $_POST['zone_type'] ?? [];

        // Если зона не была выбрана, создаём новую запись
        if (empty($zone_id) && !empty($zone_name)) {
            try {
                // Добавляем зону в таблицу zones
                $stmt = $pdo->prepare("INSERT INTO zones (name, type) VALUES (?, ?)");
                $stmt->execute([$zone_name, implode(',', $zone_types)]);

                // Получаем ID только что вставленной зоны
                $zone_id = $pdo->lastInsertId();
            } catch (Exception $e) {
                echo "Ошибка при добавлении зоны: " . $e->getMessage();
                exit;
            }
        }

        // Если выбрана существующая зона
        if (!empty($zone_id)) {
            // Получаем данные существующей зоны, если необходимо
            $stmt = $pdo->prepare("SELECT id FROM zones WHERE id = ?");
            $stmt->execute([$zone_id]);
            $existing_zone = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existing_zone) {
                echo "Зона не найдена.";
                exit;
            }
        }

        // Сохранение локации
        try {
            // Создаём новую локацию, добавляя ID зоны
            $stmt = $pdo->prepare("INSERT INTO locations (name, description, x, y, z, action, zone_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $x, $y, $z, $action, $zone_id]);

            // Получаем ID только что вставленной локации
            $location_id = $pdo->lastInsertId();

            // Обрабатываем направления
            $directions = [
                'north' => ['name' => $_POST['north_name'] ?? null, 'target' => $_POST['north_target_location_id'] ?? null],
                'south' => ['name' => $_POST['south_name'] ?? null, 'target' => $_POST['south_target_location_id'] ?? null],
                'west' => ['name' => $_POST['west_name'] ?? null, 'target' => $_POST['west_target_location_id'] ?? null],
                'east' => ['name' => $_POST['east_name'] ?? null, 'target' => $_POST['east_target_location_id'] ?? null],
                'up' => ['name' => $_POST['up_name'] ?? null, 'target' => $_POST['up_target_location_id'] ?? null],
                'down' => ['name' => $_POST['down_name'] ?? null, 'target' => $_POST['down_target_location_id'] ?? null],
            ];

            foreach ($directions as $direction => $direction_data) {
                if (!empty($direction_data['target']) && !empty($direction_data['name'])) {
                    $stmt = $pdo->prepare("INSERT INTO directions (location_id, direction_name, target_location_id) VALUES (?, ?, ?)");
                    $stmt->execute([$location_id, $direction_data['name'], $direction_data['target']]);
                }
            }

            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            echo "Ошибка при сохранении локации: " . $e->getMessage();
        }
    }


    ?>

    <!-- HTML форма остаётся без изменений -->


    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="CSS/add_location.css">
        <title>Добавить локацию</title>
    </head>
    <body>

        <div class="pipboy-header">
            <div class="pipboy-logo">Администрирование Локаций</div>
            <div class="pipboy-time">Текущее время: <?= date('H:i:s') ?></div>
        </div>

        <div class="pipboy-screen">
            <h1>Добавить локацию</h1>

            <div class="form-container">
                <form method="POST" action="">

                    <h2>Выбор зоны</h2>
                    <label for="zone_id">Выберите зону:</label>
                    <select id="zone_id" name="zone_id" >
                        <option value="" disabled selected>Выберите зону</option>
                        <?php foreach ($zones as $zone): ?>
                            <option value="<?= $zone['id']; ?>"><?= htmlspecialchars($zone['name']); ?></option>
                        <?php endforeach; ?>
                    </select><br><br>

                    <h2>Информация о зоне</h2>
                    <label for="zone_name">Название зоны:</label>
                    <input type="text" id="zone_name" name="zone_name" ><br><br>

                    <label for="zone_type">Тип зоны:</label><br>
                    <input type="checkbox" id="pve" name="zone_type[]" value="PvE">
                    <label for="pve">PvE</label><br>
                    <input type="checkbox" id="pvp" name="zone_type[]" value="PvP">
                    <label for="pvp">PvP</label><br>
                    <input type="checkbox" id="protected" name="zone_type[]" value="Охраняемая">
                    <label for="protected">Охраняемая</label><br><br>



                    <label for="name">Название локации</label>
                    <input type="text" name="name" id="name" required>

                    <label for="description">Описание локации</label>
                    <textarea name="description" id="description" rows="4" ></textarea>

                    <label for="x">Координата X</label>
                    <input type="number" name="x" id="x" required value="<?= $_GET['x'] ?? '' ?>">

                    <label for="y">Координата Y</label>
                    <input type="number" name="y" id="y" required value="<?= $_GET['y'] ?? '' ?>">

                    <label for="z">Координата Z</label>
                    <input type="number" name="z" id="z" required>

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

                    <label>Направления</label>
                    <div>
                        <input type="checkbox" name="north" id="north" value="1">
                        <label for="north">Север</label>
                        <select name="north_target_location_id" id="north_target_location_id">
                            <option value="">Не указано</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?= $location['id'] ?>"><?= $location['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <input type="checkbox" name="south" id="south" value="1">
                        <label for="south">Юг</label>
                        <select name="south_target_location_id" id="south_target_location_id">
                            <option value="">Не указано</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?= $location['id'] ?>"><?= $location['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <input type="checkbox" name="west" id="west" value="1">
                        <label for="west">Запад</label>
                        <select name="west_target_location_id" id="west_target_location_id">
                            <option value="">Не указано</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?= $location['id'] ?>"><?= $location['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <input type="checkbox" name="east" id="east" value="1">
                        <label for="east">Восток</label>
                        <select name="east_target_location_id" id="east_target_location_id">
                            <option value="">Не указано</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?= $location['id'] ?>"><?= $location['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <input type="checkbox" name="up" id="up" value="1">
                        <label for="up">Вверх</label>
                        <select name="up_target_location_id" id="up_target_location_id">
                            <option value="">Не указано</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?= $location['id'] ?>"><?= $location['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <input type="checkbox" name="down" id="down" value="1">
                        <label for="down">Вниз</label>
                        <select name="down_target_location_id" id="down_target_location_id">
                            <option value="">Не указано</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?= $location['id'] ?>"><?= $location['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit">Добавить локацию</button>
                </form>
            </div>
        </div>
    </div>

    </body>
    </html>
