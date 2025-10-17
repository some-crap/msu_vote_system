<?php
// superuser_dashboard.php

session_start();
require 'db_connection.php';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['is_superuser']) {
    header('Location: admin_login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель Суперпользователя</title>
    <style>
        /* Ваши стили оформления */
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            color: #333;
            padding: 20px;
            text-align: center;
        }

        h1, h2 {
            color: rgb(49, 36, 229);
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
        }

        form, .section {
            margin-top: 30px;
            text-align: left;
        }

        label {
            display: block;
            margin: 10px 0 5px;
            font-size: 1.1em;
        }

        input[type="text"],
        select {
            padding: 8px;
            width: 100%;
            font-size: 1em;
            margin-bottom: 15px;
        }

        input[type="submit"],
        .button {
            background-color: rgb(49, 36, 229);
            color: #fff;
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            margin-top: 10px;
            text-decoration: none;
        }

        input[type="submit"]:hover,
        .button:hover {
            background-color: rgb(111, 66, 244);
        }

        .logout {
            float: right;
            margin-top: -50px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin_logout.php" class="logout button">Выйти</a>
        <h1>Панель Суперпользователя</h1>

        <div class="section">
            <h2>Создать нового пользователя</h2>
            <form action="create_user.php" method="post">
                <label for="username">Логин:</label>
                <input type="text" name="username" id="username" required>

                <label for="role">Роль:</label>
                <select name="role" id="role" required>
                    <option value="admin">Администратор факультета</option>
                    <option value="commission">Член ЦИК (наблюдатель)</option>
                </select>

                <label for="faculty">Факультет (только для администратора):</label>
                <input type="text" name="faculty" id="faculty">

                <input type="submit" value="Создать пользователя">
            </form>
        </div>
    </div>
</body>
</html>
