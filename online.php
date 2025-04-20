<?php
// online.php

session_start();
require 'db.php';

// Проверка, что пользователь авторизован
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Получаем список пользователей, которые в данный момент онлайн (например, сессии активны)
$stmt = $pdo->query("SELECT id, username FROM users WHERE last_activity > NOW() - INTERVAL 10 MINUTE");
$online_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кто онлайн</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #1e1e1e;
            color: #e0e0e0;
            text-align: center;
            margin: 0;
            padding: 0;
        }

        h1 {
            margin: 20px 0;
            font-size: 32px;
        }

        .user-list {
            margin-top: 20px;
            text-align: left;
            width: 50%;
            margin: 0 auto;
        }

        .user-item {
            background: #3a3a3a;
            padding: 15px;
            margin: 5px 0;
            border-radius: 8px;
        }

        .back-button {
            display: inline-block;
            background: #3a3a3a;
            padding: 10px 20px;
            margin-top: 30px;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            transition: 0.3s;
        }

        .back-button:hover {
            background: #5c5c5c;
        }
    </style>
</head>
<body>

<h1>Кто онлайн</h1>

<?php if (count($online_users) > 0): ?>
    <div class="user-list">
        <?php foreach ($online_users as $user): ?>
            <div class="user-item">
                <p><?= htmlspecialchars($user['username']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p>В данный момент нет пользователей онлайн.</p>
<?php endif; ?>

<!-- Кнопка "Назад" -->
<a href="menu.php" class="back-button">Назад в меню</a>

</body>
</html>

























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