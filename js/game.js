window.addEventListener('load', function () {
    // Проверяем, существует ли уведомление о зоне
    var zoneWarning = document.getElementById('zone-warning');
    if (zoneWarning) {
        setTimeout(function () {
            zoneWarning.style.display = 'none'; // Скрываем уведомление
        }, 5000); // 5000 миллисекунд = 5 секунд
    }
});

function updateNPCs() {
    let locationId = <?php echo $current_location['id']; ?>; // Получаем id текущей локации

    fetch(`move_npc.php?location_id=${locationId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Ошибка HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            const npcList = document.querySelector('.npc-list');
            if (!npcList) return; // Проверяем, существует ли элемент

            npcList.innerHTML = ''; // Очищаем список NPC

            // Добавляем NPC в текущую локацию
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
                if (!notificationList) return;

                notificationList.innerHTML = ''; // Очищаем старые уведомления
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

// Обновляем NPC каждые 5 секунд
setInterval(updateNPCs, 5000);

function updatePlayers(players) {
    const playerList = document.querySelector('.online-players ul');
    if (!playerList) return; // Проверяем, существует ли элемент

    playerList.innerHTML = ''; // Очищаем список

    players.forEach(player => {
        if (player.id !== <?php echo $user['id']; ?>) { // Исключаем текущего пользователя
            const playerItem = document.createElement('li');
            playerItem.innerHTML = `
                <h4><a href="user.php?id=${player.id}">${player.username}</a></h4>
            `;
            playerList.appendChild(playerItem);
        }
    });
}

function loadPlayers() {
    const location_id = <?php echo $user['location_id']; ?>; // ID текущей локации

    fetch(`get_players.php?location_id=${location_id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Ошибка HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.players) {
                updatePlayers(data.players);
            }
        })
        .catch(error => console.error('Ошибка загрузки игроков:', error));
}

// Обновляем список игроков каждую секунду
setInterval(loadPlayers, 1000);

// Загружаем список игроков при загрузке страницы
loadPlayers();
