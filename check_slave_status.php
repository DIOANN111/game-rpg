<?php
require 'db.php';
require 'rab/rab.php'; // Файл с функцией checkSlaveStatus
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Не авторизован']);
    exit;
}

$user_id = $_SESSION['user_id'];
$is_slave = checkSlaveStatus($user_id, $pdo); // Проверяем, раб ли игрок

echo json_encode(['is_slave' => $is_slave]);
exit;
?>
