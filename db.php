<?php
$host = 'localhost';   // Хост базы данных
$dbname = 'test';      // Имя базы данных
$username = 'root';    // Имя пользователя
$password = 'mysql';   // Пароль

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // Вывод ошибок
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   // Получение данных в виде ассоциативного массива
        PDO::ATTR_EMULATE_PREPARES => false                 // Отключение эмуляции подготовленных запросов
    ]);
} catch (PDOException $e) {
    exit("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>
