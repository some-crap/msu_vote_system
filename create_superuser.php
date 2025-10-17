<?php
// create_superuser.php

require 'db_connection.php';

// Проверяем, есть ли уже суперпользователь
$stmt = $pdo->prepare("SELECT * FROM admins WHERE username = 'yakov'");
$stmt->execute();
if ($stmt->fetch()) {
    echo "Суперпользователь уже существует.";
    exit();
}

// Создаем суперпользователя
$username = '';
$password = '';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$faculty = ''; // Суперпользователь не привязан к факультету
$role = 'superuser';

$stmt_insert = $pdo->prepare("INSERT INTO admins (username, password, faculty, role, superuser) VALUES (?, ?, ?, ?, TRUE)");
$stmt_insert->execute([$username, $hashed_password, $faculty, $role]);

echo "Суперпользователь создан.";
?>
