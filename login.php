<?php
//login.php
session_start(); // Начинаем сессию

// Проверяем, авторизован ли пользователь
if (isset($_SESSION['user_id'])) {
    // Если пользователь авторизован, перенаправляем его на главное меню
    header("Location: game.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'db.php'; // Подключаем базу данных

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Проверка данных в базе
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Устанавливаем сессию
        $_SESSION['user_id'] = $user['id'];
        header("Location: game.php"); // Перенаправляем на главное меню
        exit;
    } else {
        $error_message = "Неверный логин или пароль";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация</title>
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

        .pipboy-nav {
            background-color: #333;
            padding: 10px;
            text-align: center;
        }

        .nav-button {
            margin: 0 10px;
            text-decoration: none;
            color: #00ff00;
            font-size: 16px;
            font-weight: bold;
        }

        .nav-button:hover {
            color: #222;
            background-color: #00ff00;
            padding: 5px 10px;
            border-radius: 5px;
        }

        .pipboy-screen {
            padding: 20px;
            text-align: center;
        }

        .pipboy-screen h1 {
            font-size: 32px;
            margin-bottom: 20px;
        }

        .input-container {
            margin-top: 20px;
            text-align: center;
        }

        /* Поля ввода теперь расположены столбиком */
        .input-container form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            max-width: 400px;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #00ff00; /* Зеленая граница */
            border-radius: 4px;
            background-color: #00ff00; /* Зеленый фон */
            font-size: 16px;
            color: #222; /* Темный текст для контраста */
        }

        input[type="submit"] {
            width: 100%;
            max-width: 400px;
            padding: 12px;
            background: #00ff00; /* Зеленый фон */
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background: #27ae60;
        }

        .error {
            color: #e74c3c;
            text-align: center;
            margin: 10px 0;
        }

        .footer {
            padding: 15px;
            background-color: #333;
            color: #a0a0a0;
            font-size: 14px;
            text-align: center;
            border-top: 2px solid #00ff00;
        }

        .footer a {
            color: #00ff00;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
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
        <div class="pipboy-logo">Прах прошлого</div>
    </div>



    <main class="pipboy-screen">
        <h1>Авторизация</h1>

        <?php if (isset($error_message)): ?>
            <div class="error"><?= $error_message ?></div>
        <?php endif; ?>

        <div class="input-container">
            <form method="POST" action="login.php">
                <input type="text" name="username" placeholder="Логин" required>
                <input type="password" name="password" placeholder="Пароль" required>
                <input type="submit" value="Войти">
            </form>

            <div class="link">
                <p>Еще нет аккаунта? </p>
                <a href="register.php">Зарегистрироваться</a>
            </div>
        </div>
    </main>

    <div class="footer">
        <p>&copy; 2025 Прах прошлого. Все права защищены.</p>
    </div>
</div>

</body>

</html>
