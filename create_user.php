<?php
// create_user.php

session_start();
require 'db_connection.php';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['is_superuser']) {
    header('Location: admin_login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $role = $_POST['role'];
    $faculty = $_POST['faculty'] ?? '';

    // Проверяем, что логин уникален
    $stmt_check = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt_check->execute([$username]);
    if ($stmt_check->fetch()) {
        echo "Пользователь с таким логином уже существует.";
        exit();
    }

    // Генерируем случайный пароль
    $password = bin2hex(random_bytes(4)); // 8 символов
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Вставляем нового пользователя
    $stmt_insert = $pdo->prepare("INSERT INTO admins (username, password, faculty, role, superuser) VALUES (?, ?, ?, ?, FALSE)");
    $stmt_insert->execute([$username, $hashed_password, $faculty, $role]);

    echo "Пользователь создан.<br>";
    echo "Логин: $username<br>";
    echo "Пароль: $password<br>";
    echo "<a href='superuser_dashboard.php'>Вернуться к панели суперпользователя</a>";
}
?>
