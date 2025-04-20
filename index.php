<?php
//index.php
session_start(); // Стартуем сессию
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="img/icona.ico" rel="shortcut icon">
    <title>Прах прошлого</title>
    <meta name="keywords" content="текстовые онлайн игры, онлайн игры на телефон, браузерные игры, текстовые приключения, онлайн ролевые игры, браузерные текстовые игры, бесплатные текстовые игры, текстовые игры без скачивания">
    <meta name="description" content="Играй бесплатно в текстовые онлайн игры на телефон: ">
    <meta name="google-site-verification" content="rYxPo9HZeHQda8aSQnvDninz-X956mubHCzpbIrYEQQ">

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
            justify-content: space-between;
            padding: 15px;
            background-color: #333;
            border-bottom: 2px solid #00ff00;
        }

        .pipboy-logo {
            font-size: 24px;
            font-weight: bold;
            color: #00ff00;
        }

        .pipboy-time {
            font-size: 18px;
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

        .game-cards {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .game-card {
            background-color: #333;
            border-radius: 10px;
            padding: 20px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            border: 2px solid #00ff00;
        }

        .game-image {
            width: 100%;
            height: auto;
            border: 2px solid #00ff00;
            border-radius: 10px;
        }

        .game-description {
            margin: 10px 0;
            font-size: 14px;
            color: #a0a0a0;
        }

        .game-button {
            display: inline-block;
            background-color: #00ff00;
            color: #222;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 10px;
        }

        .game-button:hover {
            background-color: #222;
            color: #00ff00;
        }

        .pipboy-footer {
            background-color: #333;
            padding: 15px;
            border-top: 2px solid #00ff00;
            display: flex;
            justify-content: space-between;
            font-size: 14px;
        }

        .footer-left {
            color: #a0a0a0;
        }

        .footer-right {
            text-align: right;
        }

        .footer-left a, .footer-right a {
            color: #00ff00;
            text-decoration: none;
        }

        .footer-left a:hover, .footer-right a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
<div class="pipboy-container">
    <?php include_once "header.php"; ?>


    <main class="pipboy-screen">
        <h1>Онлайн игры на телефон</h1>
        <div class="game-cards">
            <div class="game-card">
                <h2>Прах прошлого:</h2>
                <div class="game-description">
                    Прах прошлого — текстовая онлайн RPG, действие которой происходит в постапокалиптическом мире. Игроки выживают среди разрушенных городов, мутантов и заброшенных технологий, выбирая позиции от одиночек до лидеров группировок. Каждый выбор влияет на будущее, цель — раскрыть тайны исчезнувшей цивилизации и выжить в этом жестоком мире.
                </div>
                <?php if (isset($_SESSION['user_id'])) { ?>
                    <a href="game.php" class="game-button">Войти в игру</a>
                <?php } else { ?>
                    <p>Для доступа в игру необходимо <a href="login.php" class="game-button">войти</a>.</p>
                <?php } ?>
            </div>
        </div>
    </main>
    <?php require 'footer.php'; ?>
</div>
</body>

</html>
