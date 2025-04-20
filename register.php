<?php
session_start(); // Начинаем сессию

// Проверяем, если пользователь уже авторизован
if (isset($_SESSION['user_id'])) {
    // Если авторизован, перенаправляем на главное меню
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'db.php'; // Подключаем базу данных

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = $_POST['email'] ?? ''; // Добавляем email

    // Проверка на совпадение паролей
    if ($password !== $confirm_password) {
        $error_message = "Пароли не совпадают";
    } else {
        // Хэшируем пароль перед сохранением
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Проверка, если пользователь уже существует (по логину или email)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
        $stmt->execute(['username' => $username, 'email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $error_message = "Пользователь с таким логином или email уже существует";
        } else {
            // Добавляем нового пользователя с email
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email) VALUES (:username, :password, :email)");
            $stmt->execute(['username' => $username, 'password' => $hashed_password, 'email' => $email]);

            // Перенаправляем на страницу логина
            header("Location: login.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background-color: #1a1a1a;
            color: #00ff00;
            margin: 0;
            padding: 0;
        }

        .pipboy-container {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            border: 2px solid #00ff00;
            border-radius: 10px;
            background-color: #222;
            box-shadow: 0 0 15px rgba(0, 255, 0, 0.5);
        }

        .pipboy-header {
            display: flex;
            justify-content: center;
            padding: 15px;
            background-color: #333;
            border-bottom: 2px solid #00ff00;
        }

        .pipboy-logo {
            font-size: 24px;
            font-weight: bold;
            color: #00ff00;
        }

        .pipboy-screen {
            padding: 20px;
            text-align: center;
        }

        .pipboy-screen h1 {
            font-size: 32px;
            margin-bottom: 20px;
        }

        /* Исправлено для правильного расположения в столбик */
        form {
            display: block;
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }

        input[type="text"], input[type="password"], input[type="email"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #00ff00; /* Зеленая граница */
            border-radius: 4px;
            background-color: #333;
            color: #00ff00; /* Зеленый текст */
            font-size: 16px;
            font-family: 'Courier New', monospace;
        }

        input[type="submit"] {
            width: 100%;
            padding: 12px;
            background: #00ff00;
            color: #222;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            font-family: 'Courier New', monospace;
        }

        input[type="submit"]:hover {
            background: #27ae60;
        }

        .error {
            color: #e74c3c;
            text-align: center;
            margin: 10px 0;
        }

        .link {
            text-align: center;
            margin-top: 10px;
        }

        /* Зелёная ссылка */
        .link a {
            color: #00ff00;
            text-decoration: none;
            font-weight: bold;
        }

        .link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="pipboy-container">
    <div class="pipboy-header">
        <div class="pipboy-logo">Регистрация</div>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="error"><?= $error_message ?></div>
    <?php endif; ?>

    <div class="pipboy-screen">
        <form method="POST" action="register.php">
            <input type="text" name="username" placeholder="Логин" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <input type="password" name="confirm_password" placeholder="Подтвердите пароль" required>
            <input type="submit" value="Зарегистрироваться">
        </form>
    </div>

    <div class="link">
        <p>Уже есть аккаунт? </p>
        <a href="login.php">Войти</a>
    </div>
</div>

</body>
</html>
