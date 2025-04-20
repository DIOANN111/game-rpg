// Пример кода, где происходит действие, связанное с рудокопом

// Функция для выполнения действия рудокопа
function mineOre($user_id) {
global $pdo;

// Получаем текущий уровень навыка рудокопа игрока
$stmt = $pdo->prepare("SELECT mining_skill FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
die('Пользователь не найден.');
}

// Увеличиваем навык на случайную величину (например, на 1-3)
$new_mining_skill = $user['mining_skill'] + rand(1, 3);

// Ограничиваем навык максимальным значением (например, 100)
if ($new_mining_skill > 100) {
$new_mining_skill = 100;
}

// Обновляем уровень навыка в базе данных
$stmt = $pdo->prepare("UPDATE users SET mining_skill = ? WHERE id = ?");
$stmt->execute([$new_mining_skill, $user_id]);

return $new_mining_skill;
}

// Пример вызова этой функции, например, когда игрок выполняет действие добычи руды
$user_id = $_SESSION['user_id']; // Получаем ID пользователя из сессии
$new_skill = mineOre($user_id);

echo "Ваш новый уровень навыка Рудокоп: " . $new_skill;
