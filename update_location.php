<?php
// update_location.php
require 'db.php';

$user_id = (int)$_POST['user_id'];
$new_location_id = (int)$_POST['new_location_id'];

// Обновляем локацию игрока в базе данных
$stmt = $pdo->prepare("UPDATE users SET location_id = ? WHERE id = ?");
$stmt->execute([$new_location_id, $user_id]);

// Возвращаем результат в формате JSON
echo json_encode(['success' => true]);
?>
