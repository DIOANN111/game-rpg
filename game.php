<?php
// game.php
require 'db.php';
require 'rab/rab.php'; // Подключаем файл с логикой рабства
require 'items/predmets.php'; // Подключаем файл с логикой работы с предметами

session_start();

// Проверка на авторизацию
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Получаем информацию о пользователе
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Восстанавливаем здоровье при входе в конкретную локацию (например, ID = 212)
    if ($user['location_id'] == 212 && $user['health'] < $user['max_health']) {
        $stmt = $pdo->prepare("UPDATE users SET health = max_health WHERE id = ?");
        $stmt->execute([$user_id]);
        $user['health'] = $user['max_health']; // Обновляем переменную пользователя

        // Уведомление о восстановлении здоровья
        $_SESSION['health_restored'] = true;
    }


    if (!$user) {
        die('Пользователь не найден.');
    }

    // Обновляем время последней активности
    $stmt = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
    $stmt->execute([$user_id]);

    // Проверка на рабство
    $can_move = !checkSlaveStatus($user_id, $pdo);

    // Если у пользователя нет локации, назначаем стандартную
    if (!$user['location_id']) {
        $default_location_id = 22; // ID стандартной локации
        $stmt = $pdo->prepare("UPDATE users SET location_id = ? WHERE id = ?");
        $stmt->execute([$default_location_id, $user_id]);
        $user['location_id'] = $default_location_id;
    }

    $old_location_id = $user['location_id']; // Сохраняем текущую локацию перед возможным перемещением

    // Логика перемещения пользователя
    if ($can_move && isset($_GET['location_id']) && $_GET['location_id'] != $old_location_id) {
        $location_id = (int)$_GET['location_id'];

        // Проверяем, существует ли направление перемещения
        $stmt = $pdo->prepare("SELECT direction_name FROM directions WHERE location_id = ? AND target_location_id = ?");
        $stmt->execute([$old_location_id, $location_id]);
        $direction = $stmt->fetchColumn() ?: "неизвестное направление";

        if ($direction) {
            // Обновляем локацию пользователя
            $stmt = $pdo->prepare("UPDATE users SET location_id = ? WHERE id = ?");
            $stmt->execute([$location_id, $user_id]);

            // Перемещаем раба игрока, если есть
            moveSlave($user_id, $location_id, $pdo);

            // Перемещаем животных, следующих за игроком
            $stmt = $pdo->prepare("UPDATE npc SET location_id = ? WHERE follow_user_id = ?");
            $stmt->execute([$location_id, $user_id]);


            header("Location: game.php?location_id=$location_id");
            exit;
        } else {
            echo "Вы не можете переместиться в эту локацию.";
        }
    }

// Получаем текущую локацию и тип зоны
    $stmt = $pdo->prepare("SELECT l.*, z.type AS zone_type FROM locations l 
                       LEFT JOIN zones z ON l.zone_id = z.id 
                       WHERE l.id = ?");
    $stmt->execute([$user['location_id']]);
    $current_location = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current_location) {
        die('Локация не найдена.');
    }
// Вынесем тип зоны в переменную
    $zone_type = $current_location['zone_type'];



// Передаем уведомление в HTML


    // Функция для получения возможных направлений
    function getPossibleDirections($location_id, $pdo)
    {
        $stmt = $pdo->prepare("SELECT d.direction_name, l.name, d.target_location_id 
                               FROM directions d 
                               JOIN locations l ON d.target_location_id = l.id
                               WHERE d.location_id = ?");
        $stmt->execute([$location_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $possible_directions = getPossibleDirections($current_location['id'], $pdo);

// Получаем NPC в локации (включая тех, кто следует за игроком)
    $stmt = $pdo->prepare("SELECT * FROM npc WHERE location_id = ? OR follow_user_id = ?");
    $stmt->execute([$current_location['id'], $user_id]);
    $npc = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // Получаем предметы в локации
    $stmt = $pdo->prepare("SELECT i.*, COUNT(li.item_id) AS item_count
                           FROM items i
                           JOIN location_items li ON i.id = li.item_id
                           WHERE li.location_id = ?
                           GROUP BY li.item_id");
    $stmt->execute([$current_location['id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Получаем игроков в локации
    $stmt = $pdo->prepare("SELECT id, username FROM users 
                           WHERE location_id = ? AND last_activity > NOW() - INTERVAL 5 MINUTE");
    $stmt->execute([$current_location['id']]);
    $players_on_location = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Получаем количество новых сообщений
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM message WHERE recipient_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $new_messages_count = $stmt->fetchColumn();

    // Помечаем сообщения как прочитанные
    $stmt = $pdo->prepare("UPDATE message SET is_read = 1 WHERE recipient_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);

    // Проверка на первый вход или на смену локации
    if (!isset($_SESSION['last_zone_type']) || $_SESSION['last_zone_type'] !== $current_location['zone_type']) {
        // Обновляем тип зоны в сессии
        $_SESSION['last_zone_type'] = $current_location['zone_type'];

        // Уведомление о смене зоны
        $_SESSION['zone_changed'] = true;
    } else {
        // Если тип зоны не изменился, не показываем уведомление
        $_SESSION['zone_changed'] = false;
    }

// Логика для отображения уведомления о зоне
    $zone_warning = '';
    if ($zone_type === 'PvP') {
        $zone_warning = "Осторожно, опасная зона";
    } elseif ($zone_type === 'Охраняемая') {
        $zone_warning = "Вы находитесь в безопасной зоне, атаки здесь невозможны.";
    } elseif ($zone_type === 'PvE') {
        $zone_warning = "В этой зоне могут быть как безопасные, так и опасные ситуации. Будьте начеку!";
    }


} catch (PDOException $e) {
    die('Ошибка базы данных: ' . $e->getMessage());
}


?>


<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="img/icona.ico" rel="shortcut icon">
    <link rel="stylesheet" href="CSS/game.css">
    <title>Прах прошлого</title>
    <style>

        .zone-warning {
            background-color: #00ff00; /* Желтый фон */
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
            color: black;
            margin-top: 20px;
        }

        .zone-warning p {
            margin: 0;
        }

        .npc-move-notifications {
            margin: 0;
            background-color: #00ff00; /* Желтый фон */
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
            color: black;
            margin-top: 20px;
        }

    </style>

    <script>
        window.onload = function() {
            // Проверяем, существует ли уведомление о зоне
            var zoneWarning = document.getElementById('zone-warning');
            if (zoneWarning) {
                setTimeout(function() {
                    zoneWarning.style.display = 'none'; // Скрываем уведомление
                }, 5000); // 5000 миллисекунд = 5 секунд
            }
        };
    </script>

</head>
<body>
<div class="pipboy-container">
    <?php if (isset($_SESSION['npc_move_notifications']) && count($_SESSION['npc_move_notifications']) > 0): ?>

        <?php unset($_SESSION['npc_move_notifications']); ?>
    <?php endif; ?>


    <?php require 'header.php'; ?>
    <?php if ($new_messages_count > 0): ?>
        <div class="new-message-notification">
            <p><strong><a href="messages.php">У вас новое сообщение!</a></strong></p>
        </div>
    <?php endif; ?>

    <div class="online-players">
        <p><strong>Здоровье:</strong> <?= htmlspecialchars($user['health']) ?></p>
        <?php if (count($players_on_location) > 0): ?>
            <ul>
                <?php foreach ($players_on_location as $player): ?>
                    <?php if ($player['id'] != $user['id']): ?>  <!-- Исключаем текущего пользователя -->
                        <h4><a href="user.php?id=<?= $player['id'] ?>"><?= htmlspecialchars($player['username']) ?></a></h4>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        <?php endif;
        // Проверяем, первый ли это вход
        if ($user['first_login']) {
            echo "<div class='first-login-message'>";
            echo "<p>Вы очнулись в какой то комнате... Придя в себя, вы поняли, что ничего не помните, ни что с вами случилось, ни кто вы и кем были...Перед вами сидит какой то не знакомый старик, он обмазывает ваши раны. и тут то вы поняли что теперь вам придется изучить мир заново... будьте бдительны, ни кто не знает что вас ждет на следующем шагу...</p>";
            echo "</div>";

            // Обновляем статус первого входа
            $stmt = $pdo->prepare("UPDATE users SET first_login = 0 WHERE id = ?");
            $stmt->execute([$user_id]);
        }
        ?>
    </div>


    <?php if ($user['role'] === 'admin'): ?>
        <form action="add_locations.php" method="get">
            <button type="submit" class="button">Создать локацию</button>
        </form>
        <form action="edit_location_form.php" method="get">
            <button type="submit" name="id" value="<?= $current_location['id'] ?>" class="button">
                Редактировать локацию
            </button>
            <input type="hidden" name="x" value="<?= $current_location['x'] ?>">
            <input type="hidden" name="y" value="<?= $current_location['y'] ?>">
            <input type="hidden" name="z" value="<?= $current_location['z'] ?>">
        </form>
    <?php endif; ?>



    <main class="pipboy-screen">
        <h1><?= htmlspecialchars($current_location['name']) ?></h1>
        <p><strong>Тип зоны:</strong> <?= htmlspecialchars($current_location['zone_type']) ?></p>

        <!-- Уведомление о типе зоны -->
        <?php if ($_SESSION['zone_changed']): ?>
            <div id="zone-warning" class="zone-warning">
                <p><strong>Уведомление:</strong> <?= htmlspecialchars($zone_warning) ?></p>
            </div>
        <?php endif; ?>



        <!-- Отображение NPC/врагов на локации -->
        <div class="npc-list">
            <?php foreach ($npc as $npc_item): ?>
                <!-- Проверка на смерть NPC -->
                <?php if ($npc_item['health'] <= 0): ?>
                    <div class="npc-item">
                        <p><?= htmlspecialchars($npc_item['name']) ?> - Этот NPC мертв.</p>
                    </div>
                <?php else: ?>
                    <div class="npc-item">
                        <form action="npc_details.php" method="GET">
                            <button type="submit" name="id" value="<?= $npc_item['id'] ?>" class="npc-button">
                                <?= htmlspecialchars($npc_item['name']) ?>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <?php if (isset($_SESSION['health_restored'])): ?>
            <div class="notification">
                Ваше здоровье полностью восстановлено!
            </div>
            <?php unset($_SESSION['health_restored']); // Убираем уведомление после вывода ?>
        <?php endif; ?>

        <!-- Чёрная Камера Хранения -->
        <?php if ($current_location['id'] === 20 || $current_location['id'] === 0 || $current_location['id'] === 1111111111111): ?>
            <div class="bank-cell">
                <?php if (!empty($bank_items)): ?>
                    <ul>
                        <?php foreach ($bank_items as $bank_item): ?>
                            <li>
                                <?= htmlspecialchars($bank_item['name']) ?> (<?= htmlspecialchars($bank_item['type']) ?>) - Количество: <?= $bank_item['count'] ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>В вашей камере хранения нет предметов.</p>
                <?php endif; ?>
                <a href="bank.php" class="description-button"><h>Чёрная Камера Хранения</h></a>
            </div>
            <hr>
        <?php endif; ?>


        <!-- Проверка на статус раба -->
        <div class="directions">
            <?php if (!$can_move): ?>
                <p>Вы не можете перемещаться, так как вы в цепях.</p>
            <?php else: ?>
                <?php foreach ($possible_directions as $direction): ?>
                    <a href="game.php?location_id=<?= $direction['target_location_id'] ?>" class="direction-button">
                        <?= htmlspecialchars($direction['direction_name']) ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Отображение предметов на локации -->
        <div class="items-list">
            <?php foreach ($items as $item): ?>
                <div class="item">
                    <a href="item_detail.php?item_id=<?= $item['id'] ?>" class="npc-button">
                        <?= htmlspecialchars($item['name']) ?> (<?= htmlspecialchars($item['type']) ?>) - Количество: <?= $item['item_count'] ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </main>


    <footer class="pipboy-footer">
        <div class="footer-left">

            <?php if ($current_location['id'] == 212): ?>
                <a href="hram.php" class="heal-button">Восстановить здоровье</a>
            <?php endif; ?>
            <a href="location_description.php?location_id=<?= $current_location['id'] ?>" class="description-button">Описание</a>
            <a href="messages.php" class="description-button">Сообщения</a>
            <a href="map.php" class="description-button">Карта</a>
            <a href="inventory.php" class="description-button">Инвентарь</a>
            <a href="menu.php" class="description-button">В меню</a>
        </div>
    </footer>
    <? require 'footer.php'?>
</div>
<script>
    function updateNPCs() {
        // Получаем id текущей локации
        let locationId = <?= $current_location['id'] ?>;

        fetch(`move_npc.php?location_id=${locationId}`)  // Передаем id локации
            .then(response => response.json())
            .then(data => {
                const npcList = document.querySelector('.npc-list');
                npcList.innerHTML = ''; // Очищаем текущий список NPC

                // Добавляем NPC, которые находятся в текущей локации
                data.npcs.forEach(npc => {
                    const npcItem = document.createElement('div');
                    npcItem.classList.add('npc-item');
                    npcItem.innerHTML = `
                <form action="npc_details.php" method="GET">
                    <button type="submit" name="id" value="${npc.id}" class="npc-button">
                        ${npc.name}
                    </button>
                </form>
                `;
                    npcList.appendChild(npcItem);
                });

                // Обработка уведомлений
                if (data.notifications && data.notifications.length > 0) {
                    const notificationList = document.querySelector('.npc-notifications');
                    data.notifications.forEach(notification => {
                        const notificationItem = document.createElement('div');
                        notificationItem.classList.add('notification-item');
                        notificationItem.textContent = notification;
                        notificationList.appendChild(notificationItem);
                    });
                }
            })
            .catch(error => console.error("Ошибка AJAX запроса:", error));
    }

    // Запускаем обновление NPC каждую секунду (или через желаемый интервал)
    setInterval(updateNPCs, 5000);  // Обновляем каждую секунду
    // Функция для обновления списка игроков
    function updatePlayers(players) {
        const playerList = document.querySelector('.online-players ul');
        playerList.innerHTML = '';  // Очищаем текущий список

        // Добавляем игроков на страницу
        players.forEach(player => {
            if (player.id !== <?= $user['id'] ?>) {  // Исключаем текущего пользователя
                const playerItem = document.createElement('li');
                playerItem.innerHTML = `
                <h4><a href="user.php?id=${player.id}">${player.username}</a></h4>
            `;
                playerList.appendChild(playerItem);
            }
        });
    }

    // Функция для загрузки списка игроков
    function loadPlayers() {
        const location_id = <?= $user['location_id'] ?>;  // ID локации текущего игрока

        // Выполняем AJAX запрос для получения списка игроков в локации
        fetch('get_players.php?location_id=' + location_id)
            .then(response => response.json())
            .then(data => {
                if (data.players) {
                    updatePlayers(data.players);  // Обновляем список игроков
                }
            })
            .catch(error => console.error('Ошибка:', error));
    }

    // Обновляем список игроков каждые 5 секунд
    setInterval(loadPlayers, 1000);

    // Инициализация списка при загрузке страницы
    loadPlayers();
</script>
</body>
</html>