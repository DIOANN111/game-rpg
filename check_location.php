<?php
// check_location.php
require 'db.php';
session_start();

// Проверка, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Получаем информацию о пользователе
    $stmt = $pdo->prepare("SELECT location_id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    // Отправляем текущую локацию пользователя
    echo json_encode(['new_location_id' => $user['location_id']]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
