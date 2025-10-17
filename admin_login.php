<?php
// admin_login.php

session_start();
require 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Поиск пользователя в базе данных
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        // Сохранение данных в сессии
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_faculty'] = $admin['faculty'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['is_superuser'] = $admin['superuser'];

        if ($_SESSION['is_superuser']) {
            header('Location: superuser_dashboard.php');
        } else {
            header('Location: admin_dashboard.php');
        }
        exit();
    } else {
        $error = "Неверный логин или пароль.";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход администратора</title>
    <style>
        /* Ваши стили оформления */
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            text-align: center;
            padding: 50px;
        }

        h2 {
            color: rgb(49, 36, 229);
        }

        form {
            display: inline-block;
            margin-top: 20px;
        }

        label {
            display: block;
            margin: 15px 0 5px;
            font-size: 1.1em;
        }

        input[type="text"],
        input[type="password"] {
            padding: 10px;
            width: 300px;
            font-size: 1em;
        }

        input[type="submit"] {
            background-color: rgb(49, 36, 229);
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            margin-top: 20px;
        }

        input[type="submit"]:hover {
            background-color: rgb(111, 66, 244);
        }

        .error {
            color: red;
        }
    </style>
</head>
<body>
    <h2>Вход администратора</h2>
    <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>
    <form action="admin_login.php" method="post">
        <label for="username">Логин:</label>
        <input type="text" name="username" id="username" required>
        <label for="password">Пароль:</label>
        <input type="password" name="password" id="password" required>
        <input type="submit" value="Войти">
    </form>
</body>
</html>
