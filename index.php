<?php
// index.php

session_start();

// Если пользователь уже вошёл как администратор или суперпользователь, перенаправляем его на соответствующую панель
if (isset($_SESSION['admin_logged_in'])) {
    if ($_SESSION['is_superuser']) {
        header('Location: superuser_dashboard.php');
        exit();
    } else {
        header('Location: admin_dashboard.php');
        exit();
    }
}

// Если пользователь переходит по ссылке для голосования с токеном
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    header("Location: vote.php?token=$token");
    exit();
}

// Если ни одно из условий не выполнено, показываем главную страницу с ссылками
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Онлайн-голосование</title>
    <style>
        /* Стили оформления */
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            color: #333;
            text-align: center;
            padding: 50px;
        }

        h1 {
            color: rgb(49, 36, 229);
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .button {
            background-color: rgb(49, 36, 229);
            color: white;
            padding: 15px 30px;
            text-align: center;
            text-decoration: none;
            font-size: 18px;
            margin: 20px 10px;
            cursor: pointer;
            border-radius: 5px;
            display: inline-block;
        }

        .button:hover {
            background-color: rgb(111, 66, 244);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Добро пожаловать в систему онлайн-голосования</h1>
        <p>Пожалуйста, выберите действие:</p>
        <a href="admin_login.php" class="button">Вход для администрации</a>
        <!-- Можно добавить дополнительные ссылки или информацию -->
    </div>
</body>
</html>
