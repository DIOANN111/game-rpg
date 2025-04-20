<?php
require 'db.php';
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    die("Ошибка: Вы не авторизованы.");
}

$user_id = $_SESSION['user_id'];
$npc_id = isset($_GET['npc_id']) ? (int) $_GET['npc_id'] : die("Ошибка: NPC не найден.");

// Получаем информацию о NPC
$stmt = $pdo->prepare("SELECT * FROM npc WHERE id = ?");
$stmt->execute([$npc_id]);
$npc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$npc) die("Ошибка: NPC не найден.");
if ($npc['npc_type'] !== 'враг') die("Этот NPC не является врагом.");
if ($npc['health'] <= 0) die("NPC уже мертв.");

// Получаем информацию об игроке
$stmt = $pdo->prepare("SELECT strength, agility, combat_skill, energy, health FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) die("Ошибка: Игрок не найден.");

// Проверка на наличие значения combat_skill у игрока, если его нет, то используем значение 0
$user_combat_skill = isset($user['combat_skill']) ? $user['combat_skill'] : 0;

// Проверка на наличие значения combat_skill у NPC, если его нет, то используем значение 0
$npc_combat_skill = isset($npc['combat_skill']) ? $npc['combat_skill'] : 0;

// Стили боя (влияют на механику)
$combat_styles = ['Берсерк', 'Тактик', 'Ловкач'];
$user_style = $combat_styles[array_rand($combat_styles)];
$npc_style = $combat_styles[array_rand($combat_styles)];

// Базовый урон игрока
$user_attack = (int) ($user['strength'] + ($user_combat_skill * 1.5));

// Динамическая защита NPC (зависит от ловкости)
$npc_dodge = $npc['agility'] * 2;
$dodge_chance = rand(1, 100);

// Проверка на уклонение NPC
if ($dodge_chance <= $npc_dodge) {
    echo "NPC " . htmlspecialchars($npc['name']) . " уклонился от атаки!<br>";
} else {
    // Тип атаки (обычная, критическая, тактическая)
    $attack_type = rand(1, 100);
    if ($attack_type <= 15) {
        $damage = $user_attack * 1.8; // Критический удар
        $attack_description = "Критический удар!";
    } elseif ($attack_type <= 30) {
        $damage = $user_attack * 1.3; // Тактический удар (снижает защиту NPC)
        $npc_dodge = max(0, $npc_dodge - 5);
        $attack_description = "Тактический удар! Теперь у NPC меньше шансов уклониться.";
    } else {
        $damage = $user_attack * 1.0; // Обычная атака
        $attack_description = "Обычная атака.";
    }

    // Выносливость влияет на атаку
    if ($user['energy'] < 20) {
        $damage *= 0.8;
        echo "Вы устали, урон снижен!<br>";
    }

    // Финальный урон и обновление здоровья NPC
    $new_health = max(0, $npc['health'] - $damage);
    $stmt = $pdo->prepare("UPDATE npc SET health = ? WHERE id = ?");
    $stmt->execute([$new_health, $npc_id]);

    echo "Вы нанесли $damage урона NPC " . htmlspecialchars($npc['name']) . "! ($attack_description)<br>";
    echo "Оставшееся здоровье врага: $new_health<br>";

    // Лог боя
    $stmt = $pdo->prepare("INSERT INTO combat_log (user_id, npc_id, damage, time) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$user_id, $npc_id, $damage]);

    // Если NPC умер
    if ($new_health == 0) {
        echo "Вы убили " . htmlspecialchars($npc['name']) . "!<br>";

        // Проверка, связан ли NPC с каким-либо квестом
        for ($i = 1; $i <= 4; $i++) {
            $quest_key = "quest_$i";
            if (isset($npc[$quest_key]) && $npc[$quest_key]) {
                $quest_id = $npc[$quest_key];
                $stmt = $pdo->prepare("UPDATE quests SET status = 'completed' WHERE id = ?");
                $stmt->execute([$quest_id]);
                echo "Статус квеста $quest_id обновлен на 'Завершен'.<br>";
                break;
            }
        }

        // Устанавливаем время возрождения NPC через 10 секунд
        $stmt = $pdo->prepare("UPDATE npc SET respawn_at = NOW() + INTERVAL 10 SECOND WHERE id = ?");
        $stmt->execute([$npc_id]);

        // Показываем NPC через 10 секунд (таймер на стороне клиента)
        echo "<script>
    setTimeout(function() {
        document.getElementById('npc_{$npc_id}').style.display = 'none';
        setTimeout(function() {
            document.getElementById('npc_{$npc_id}').style.display = 'block'; // Показываем NPC через 10 секунд
        }, 10000);
    }, 0);
  </script>";

        echo "<br><a href='game.php'>Вернуться в игру</a>";
        exit;
    }

// Проверка возрождения NPC
    if ($npc['respawn_at'] && new DateTime() > new DateTime($npc['respawn_at'])) {
        // NPC возродился, восстанавливаем его здоровье
        $stmt = $pdo->prepare("UPDATE npc SET health = 100, respawn_at = NULL WHERE id = ?");
        $stmt->execute([$npc_id]);
        echo "NPC возродился!<br>";
    }



}

// **Ответный удар NPC**
$npc_attack = (int) $npc['damage']; // Урон NPC теперь из базы данных
$user_dodge = $user['agility'] * 2;
$dodge_chance = rand(1, 100);

// Проверяем, уклонился ли игрок
if ($dodge_chance <= $user_dodge) {
    echo "Вы уклонились от атаки NPC " . htmlspecialchars($npc['name']) . "!<br>";
} else {
    // Ограничиваем максимальный урон NPC
    $max_damage = 100; // Максимальный урон NPC
    $npc_damage = min(rand(max(1, $npc_attack - 3), $npc_attack + 3), $max_damage);

    echo "Сгенерированный урон NPC: $npc_damage<br>";

    // Обновляем здоровье игрока
    $new_user_health = max(0, $user['health'] - $npc_damage);
    $stmt = $pdo->prepare("UPDATE users SET health = ? WHERE id = ?");
    $stmt->execute([$new_user_health, $user_id]);

    echo "NPC " . htmlspecialchars($npc['name']) . " атакует и наносит вам $npc_damage урона!<br>";
    echo "Ваше текущее здоровье: $new_user_health<br>";

    // Если игрок умер
    if ($new_user_health == 0) {
        echo "Вы погибли в бою!<br>";
        echo "<a href='hram.php'>Возродиться</a>";
        exit;
    }
}

// Обновляем энергию игрока
$stmt = $pdo->prepare("UPDATE users SET energy = GREATEST(0, energy - 5) WHERE id = ?");
$stmt->execute([$user_id]);

?>

<br><br>
<a href="attack.php?npc_id=<?= $npc_id ?>">Атаковать снова</a>
<a href="game.php">Вернуться в игру</a>

<!-- Кнопка для восстановления здоровья -->
<br><br>
<a href="hram.php">Восстановить здоровье</a>
