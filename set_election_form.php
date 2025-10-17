<?php
// set_election_form.php

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_role'] != 'admin') {
    header('Location: admin_login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Создать выборы</title>
    <style>
        /* Ваши стили оформления */
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            text-align: center;
            padding: 20px;
        }

        h2 {
            color: rgb(49, 36, 229);
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
        }

        form {
            text-align: left;
        }

        label {
            display: block;
            margin: 15px 0 5px;
            font-size: 1.1em;
        }

        input[type="text"],
        input[type="number"],
        input[type="file"],
        textarea {
            padding: 8px;
            width: 100%;
            font-size: 1em;
            margin-bottom: 15px;
        }

        input[type="submit"] {
            background-color: rgb(49, 36, 229);
            color: #fff;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
        }

        input[type="submit"]:hover {
            background-color: rgb(111, 66, 244);
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Создать новые выборы</h2>
        <form action="set_election.php" method="post" enctype="multipart/form-data">
            <label for="course">Направление голосования (без сокращений):</label>
            <input type="text" name="course" id="course" required>

            <label for="quota">За скольких кандидатов может голосовать избиратель:</label>
            <input type="number" name="quota" id="quota" required>

            <label for="amount_of_students_real">Сколько человек реально на курсе (может отличаться от количества известных почт):</label>
            <input type="number" name="amount_of_students_real" id="amount_of_students_real" required>

            <label for="real_quota">Сколько кандидатов будет избрано (квота):</label>
            <input type="number" name="real_quota" id="real_quota" required>

            <label for="candidates">Список кандидатов (введите ФИО каждого кандидата с новой строки):</label>
            <textarea name="candidates" id="candidates" rows="5" required></textarea>
            <label for="abstain_option">
                <input type="checkbox" id="abstain_option" name="abstain_option" value="1">
                Включить опцию "воздержаться"
            </label>
            <label for="against_all_option">
                <input type="checkbox" id="against_all_option" name="against_all_option" value="1">
                Включить опцию "против всех"
            </label><br>

            <label for="voters_file">Загрузите файл со списком избирателей (Excel). Если не знаете формат - спросите:</label>
            <input type="file" name="voters_file" id="voters_file" accept=".xlsx, .xls" required>

            <input type="submit" value="Создать выборы">
        </form>
    </div>
</body>
</html>
