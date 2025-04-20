
<?php
// get_players.php

require 'db.php';
session_start();

// Проверка, что пользователь авторизован
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Не авторизован']);
    exit;
}

$user_id = $_SESSION['user_id'];
$location_id = isset($_GET['location_id']) ? (int)$_GET['location_id'] : 0;

if (!$location_id) {
    echo json_encode(['error' => 'Не указана локация']);
    exit;
}

// Получаем игроков в текущей локации
$stmt = $pdo->prepare("SELECT id, username FROM users WHERE location_id = ? AND last_activity > NOW() - INTERVAL 5 MINUTE");
$stmt->execute([$location_id]);
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['players' => $players]);
