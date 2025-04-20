<?php
session_start();
require 'db.php'; // Подключение к базе данных

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Получаем информацию о текущем пользователе
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('Пользователь не найден.');
}

// Получаем id игрока из GET-запроса
$player_id = $_GET['id'] ?? null;
if ($player_id) {
    // Получаем информацию о выбранном игроке
    $player_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $player_stmt->execute([$player_id]);
    $selected_player = $player_stmt->fetch(PDO::FETCH_ASSOC);

    if ($selected_player) {
        // Проверяем вес инвентаря и увеличиваем силу, если нужно
        $inventory_stmt = $pdo->prepare("SELECT SUM(items.weight * inventory.quantity) AS total_weight FROM inventory JOIN items ON inventory.item_id = items.id WHERE inventory.user_id = ?");
        $inventory_stmt->execute([$player_id]);
        $inventory = $inventory_stmt->fetch(PDO::FETCH_ASSOC);

        $current_weight = (float)$inventory['total_weight'] ?? 0;
        $max_weight = $selected_player['carrying_capacity_skill'] * 10;

        if ($current_weight > $max_weight) {
            $strength_update = $pdo->prepare("UPDATE users SET strength_skill = strength_skill + 1 WHERE id = ?");
            $strength_update->execute([$player_id]);
        }

        // Выводим информацию о навыках игрока
        echo "<h1>Навыки игрока: " . htmlspecialchars($selected_player['username']) . "</h1>";
        echo "<div class='skills-details'>";

        $skills = [
            'strength_skill' => 'Сила',
            'agility_skill' => 'Ловкость',
            'intelligence_skill' => 'Интеллект',
            'accuracy_skill' => 'Меткость',
            'dodge_skill' => 'Уклон',
            'heavy_armor_skill' => 'Тяжелая броня',
            'stealth_skill' => 'Скрытность',
            'counterattack_skill' => 'Ответный удар',
            'increased_health_skill' => 'Повышенное здоровье',
            'carrying_capacity_skill' => 'Грузоподъемность',
            'trading_skill' => 'Торговля',
            'repair_skill' => 'Ремонт',
            'two_hand_weapon_skill' => 'Двуручное оружие',
            'blacksmith_skill' => 'Кузнец',
            'hunter_skill' => 'Охотник',
            'treasure_hunting_skill' => 'Кладоискатель',
            'jeweler_skill' => 'Ювелир',
            'cook_skill' => 'Повар',
            'fisherman_skill' => 'Рыболов',
            'theft_skill' => 'Воровство',
            'druid_skill' => 'Друид',
            'one_hand_weapon_skill' => 'Одноручное оружие',
            'firearms_skill' => 'Огнестрельное оружие',
            'hand_to_hand_skill' => 'Рукопашный бой',
            'regeneration_skill' => 'Регенерация',
            'healing_skill' => 'Лечение',
            'lumberjack_skill' => 'Лесоруб',
            'carpenter_skill' => 'Плотник',
            'tanner_skill' => 'Кожевник',
            'light_armor_skill' => 'Легкая броня',
            'improved_backpack_skill' => 'Улучшенный рюкзак',
            'second_chance_skill' => 'Второй шанс',
            'full_gear_skill' => 'Полный комплект',
            'mining_skill' => 'Рудокоп'
        ];

        foreach ($skills as $key => $label) {
            echo "<p><strong>{$label}:</strong> " . htmlspecialchars($selected_player[$key]) . "</p>";
        }

        echo "</div>";
    } else {
        echo "<p>Игрок не найден.</p>";
    }
} else {
    echo "<p>Неверный запрос. Параметр id не передан.</p>";
}
?>

<a href="user.php?id=<?php echo htmlspecialchars($player_id); ?>" class="menu-button">Назад</a>

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

    .skills-details {
        background: #3a3a3a;
        padding: 20px;
        margin: 20px;
        border-radius: 8px;
        color: #fff;
    }

    .menu-button {
        display: inline-block;
        background: #3a3a3a;
        padding: 10px 20px;
        margin: 10px;
        color: #fff;
        text-decoration: none;
        border-radius: 8px;
        transition: 0.3s;
    }

    .menu-button:hover {
        background: #5c5c5c;
    }
</style>
