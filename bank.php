<?php
session_start();
require_once 'db.php'; // Подключаем базу данных

// Устанавливаем часовой пояс Москвы
date_default_timezone_set('Asia/Yekaterinburg');


// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
header("Location: login.php");
exit();
}

$userId = $_SESSION['user_id'];

// Получаем текущую локацию пользователя
$stmt = $pdo->prepare("SELECT location_id FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userLocation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userLocation) {
die("Ошибка: Локация пользователя не найдена.");
}

// Проверяем именно 21, а не 20
$isLocation21 = $userLocation['location_id'] == 20;

// Получаем информацию об аренде
$stmt = $pdo->prepare("SELECT last_payment_date, rent_paid FROM rental_storage WHERE user_id = ?");
$stmt->execute([$userId]);
$rentalInfo = $stmt->fetch(PDO::FETCH_ASSOC);

// Проверяем, истекло ли время аренды
$now = new DateTime();
$rentalExpired = false;
$remainingTime = null;

if ($rentalInfo && $rentalInfo['last_payment_date']) {
$lastPaymentTime = new DateTime($rentalInfo['last_payment_date']);
$endOfDay = clone $lastPaymentTime;
$endOfDay->setTime(23, 59, 59); // Устанавливаем конец дня по Москве

$remainingTime = $now->diff($endOfDay);

// Если срок аренды истёк и аренда не оплачена
if ($now > $endOfDay && !$rentalInfo['rent_paid']) {
$rentalExpired = true;
transferItemsToNpc($userId);
}
}


// Функция для переноса предметов из bank_storage в npc_items
function transferItemsToNpc($userId) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM bank_storage WHERE user_id = ?");
    $stmt->execute([$userId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($items) {
        foreach ($items as $item) {
            $stmtInsert = $pdo->prepare("INSERT INTO npc_items (item_id, user_id, quantity)
                                         VALUES (?, ?, ?)");
            $stmtInsert->execute([$item['item_id'], 0, $item['quantity']]);

            $stmtDelete = $pdo->prepare("DELETE FROM bank_storage WHERE id = ?");
            $stmtDelete->execute([$item['id']]);
        }
        echo "<p style='color: red; font-weight: bold;'>Аренда не оплачена вовремя. Все предметы были переданы NPC.</p>";
    }
}

// Функция для оплаты аренды
function payRent($userId) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT quantity FROM user_currency WHERE user_id = ? AND currency_name = 'Монеты'");
    $stmt->execute([$userId]);
    $userCurrency = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userCurrency && $userCurrency['quantity'] >= 500) {
        $stmt = $pdo->prepare("UPDATE user_currency SET quantity = quantity - 500 WHERE user_id = ? AND currency_name = 'Монеты'");
        $stmt->execute([$userId]);

        $stmt = $pdo->prepare("INSERT INTO rental_storage (user_id, last_payment_date, rent_paid) 
                               VALUES (?, NOW(), 1)
                               ON DUPLICATE KEY UPDATE last_payment_date = NOW(), rent_paid = 1");
        $stmt->execute([$userId]);

        echo "<p style='color: green; font-weight: bold;'>Аренда камеры хранения оплачена. 500 монет снято.</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>Ошибка: У вас недостаточно монет для оплаты аренды.</p>";
    }
}

// Обработчик формы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'pay_rent') {
        payRent($userId);
    }
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="CSS/styles.css">
    <title>Банковская ячейка</title>
    <script>
        function updateTimer() {
            let remainingTime = <?php echo json_encode($remainingTime ? $remainingTime->format('%H:%I:%S') : "00:00:00"); ?>;

            if (remainingTime !== "00:00:00") {
                let parts = remainingTime.split(':');
                let hours = parseInt(parts[0], 10);
                let minutes = parseInt(parts[1], 10);
                let seconds = parseInt(parts[2], 10);

                function tick() {
                    if (hours === 0 && minutes === 0 && seconds === 0) {
                        document.getElementById('timer').innerHTML = "Время вышло!";
                        return;
                    }

                    if (seconds > 0) {
                        seconds--;
                    } else {
                        if (minutes > 0) {
                            minutes--;
                            seconds = 59;
                        } else if (hours > 0) {
                            hours--;
                            minutes = 59;
                            seconds = 59;
                        }
                    }

                    document.getElementById('timer').innerHTML =
                        `Осталось: ${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

                    setTimeout(tick, 1000);
                }

                tick();
            }
        }
    </script>
</head>
<body onload="updateTimer()">
<h1>Ваши банковские ячейки</h1>

<?php if ($isLocation21 && (!$rentalInfo || !$rentalInfo['rent_paid'])): ?>
    <p style="color: red; font-weight: bold;">Сначала оплатите аренду, чтобы воспользоваться банком.</p>
    <button disabled>Забрать из банка</button><br><br>
    <button disabled>Положить в банк</button><br><br>
<?php else: ?>
    <a href="retrieve.php"><button>Забрать из банка</button></a><br><br>
    <a href="store.php"><button>Положить в банк</button></a><br><br>
<?php endif; ?>

<!-- Показываем кнопку оплаты аренды только если локация пользователя 21 -->
<?php if ($isLocation21): ?>
    <form method="POST">
        <input type="hidden" name="action" value="pay_rent">
        <button type="submit">Оплатить аренду (500 монет)</button><br><br>
    </form>

    <?php if ($remainingTime): ?>
        <p style="color: orange; font-weight: bold;">
            Время до конца оплаты аренды: <span id="timer"><?= $remainingTime->format('%H:%I:%S') ?></span>
        </p>
    <?php endif; ?>
<?php endif; ?>

<a href="game.php"><button>Назад в игру</button></a>

</body>
</html>
